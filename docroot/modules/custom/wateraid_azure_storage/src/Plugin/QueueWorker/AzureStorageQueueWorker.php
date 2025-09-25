<?php

namespace Drupal\wateraid_azure_storage\Plugin\QueueWorker;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\wateraid_azure_storage\AzureStorageQueueErrorLogEntry;
use Drupal\wateraid_azure_storage\AzureStorageQueueMessage;
use Drupal\wateraid_azure_storage\AzureStorageQueueWebformServiceInterface;
use Drupal\wateraid_azure_storage\Exception\BaseAzureStorageQueueException;
use Drupal\wateraid_azure_storage\Exception\DisabledCronException;
use Drupal\webform\WebformSubmissionStorageInterface;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process a queue of AzureStorageMessage items to Azure.
 *
 * @QueueWorker(
 *   id = "wateraid_azure_storage_queue",
 *   title = @Translation("WaterAid Azure Storage Queue worker"),
 *   cron = {"time" = 60}
 * )
 */
class AzureStorageQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The maximum amount of times a single queue item is allowed for retries.
   */
  public const MAX_RETRY_THRESHOLD = 5;

  /**
   * Azure Storage Queue Webform service.
   */
  protected AzureStorageQueueWebformServiceInterface $azureStorageQueueWebformService;

  /**
   * Webform Submission storage interface.
   */
  protected WebformSubmissionStorageInterface $webformSubmissionStorage;

  /**
   * Logger service.
   */
  protected LoggerChannelInterface $logger;

  /**
   * Queue interface.
   */
  protected QueueInterface $azureStorageErrorNotifyQueue;

  /**
   * The settings config.
   */
  protected ImmutableConfig $config;

  /**
   * The time service.
   */
  protected TimeInterface $time;

  /**
   * AzureStorageQueue constructor.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\wateraid_azure_storage\AzureStorageQueueWebformServiceInterface $azure_storage_queue_webform_service
   *   Azure Storage Webform service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Manages entity type plugin definitions.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The Queue Factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The date time instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(array $configuration, string $plugin_id, mixed $plugin_definition, AzureStorageQueueWebformServiceInterface $azure_storage_queue_webform_service, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger, QueueFactory $queue_factory, ConfigFactoryInterface $config_factory, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->azureStorageQueueWebformService = $azure_storage_queue_webform_service;
    $this->webformSubmissionStorage = $entity_type_manager->getStorage('webform_submission');
    $this->logger = $logger;
    $this->azureStorageErrorNotifyQueue = $queue_factory->get('wateraid_azure_storage_error_notify_queue');
    $this->config = $config_factory->get('wateraid_azure_storage.settings');
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('wateraid_azure_storage.webform_service'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.wateraid_azure_storage'),
      $container->get('queue'),
      $container->get('config.factory'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    /** @var \Drupal\wateraid_azure_storage\AzureStorageQueueMessage $data */
    if ($data instanceof AzureStorageQueueMessage === FALSE) {
      $this->logger->error('Invalid queue item.');
      throw new \Exception('Something is wrong here.');
    }
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    if ($webform_submission = $this->webformSubmissionStorage->load($data->getId())) {
      try {
        $this->azureStorageQueueWebformService->postWebformSubmission($webform_submission, 'queue');
      }
      catch (\Exception $e) {
        $this->logger->error('Submission ID: %sid, Error: %error', [
          '%sid' => $webform_submission->id(),
          '%error' => $e->getMessage(),
        ]);

        // Don't attempt retries for Exceptions that are purposely being
        // silenced (see MES-17).
        if ($e instanceof BaseAzureStorageQueueException && $e->isSilenced() === TRUE) {
          return;
        }

        // Get retry count.
        $retry_count = (int) $webform_submission->get('message_retries')->value ?: 0;

        // Do count any non-silent failures reaching this point towards the
        // retry threshold, and only if not by a disabled cron.
        if ($e instanceof DisabledCronException === FALSE) {

          $webform_submission
            ->set('message_retries', ++$retry_count)
            ->save();

          $this->logger->info('Submission ID: %sid, Retry Count: %retry_count', [
            '%sid' => $webform_submission->id(),
            '%retry_count' => $retry_count,
          ]);

          // Check if we are supposed to report to an error mailbox.
          if ((bool) $this->config->get('error_notify') === TRUE) {
            // Log an entry for scheduled report to error mailbox.
            $log_message = 'Submission ID: ' . $webform_submission->id() . ', Retry Count: ' . $retry_count . ', Webform ID: ' . $webform_submission->getWebform()->id();
            $error_log_entry = (new AzureStorageQueueErrorLogEntry())
              ->setTimestamp($this->time->getRequestTime())
              ->setRetryCount($retry_count)
              ->setMessage($log_message);

            $this->azureStorageErrorNotifyQueue->createItem($error_log_entry);
          }
        }

        if ($retry_count >= self::MAX_RETRY_THRESHOLD) {
          return;
        }

        // Treat a ServiceException as a reason to suspend the queue.
        if ($e instanceof ServiceException) {
          throw new SuspendQueueException($e->getMessage());
        }

        // And any other Exception for default queue error handling.
        throw new \Exception($e->getMessage());
      }
    }
  }

}
