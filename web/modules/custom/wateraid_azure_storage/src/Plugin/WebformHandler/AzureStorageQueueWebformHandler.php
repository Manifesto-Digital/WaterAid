<?php

namespace Drupal\wateraid_azure_storage\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\wateraid_azure_storage\AzureStorageQueueMessage;
use Drupal\wateraid_azure_storage\AzureStorageQueueWebformServiceInterface;
use Drupal\wateraid_azure_storage\Exception\BaseAzureStorageQueueException;
use Drupal\wateraid_azure_storage\Exception\InvalidEnvironmentException;
use Drupal\wateraid_azure_storage\Utility\CloneHandlerDataHelper;
use Drupal\wateraid_azure_storage\Utility\ConnectionHelper;
use Drupal\wateraid_azure_storage\Utility\MessageMetadataHelper;
use Drupal\wateraid_azure_storage\Utility\MessageSubmissionDataHelper;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform handler for Azure Storage.
 *
 * @package Drupal\wateraid_azure_storage\Plugin\WebformHandler
 *
 * @WebformHandler(
 *   id = "wateraid_azure_storage_queue",
 *   label = @Translation("WaterAid Azure Storage Queue"),
 *   category = @Translation("WaterAid Azure Storage"),
 *   description = @Translation("Processes Azure Storage Queue."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class AzureStorageQueueWebformHandler extends WebformHandlerBase {

  /**
   * Azure Storage Queue Webform service.
   */
  protected AzureStorageQueueWebformServiceInterface $azureStorageQueueWebformService;

  /**
   * Queue interface.
   */
  protected QueueInterface $azureStorageQueue;

  /**
   * Logger service.
   */
  protected LoggerChannelInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setAzureStorageQueueWebformService($container->get('wateraid_azure_storage.webform_service'));
    $instance->setAzureStorageQueue($container->get('queue'));
    $instance->setLogger($container->get('logger.channel.wateraid_azure_storage'));
    return $instance;
  }

  /**
   * Sets the Azure Storage Queue Webform service.
   *
   * @param \Drupal\wateraid_azure_storage\AzureStorageQueueWebformServiceInterface $azure_storage_queue_webform_service
   *   Azure Storage Webform service.
   *
   * @return $this
   */
  protected function setAzureStorageQueueWebformService(AzureStorageQueueWebformServiceInterface $azure_storage_queue_webform_service): static {
    $this->azureStorageQueueWebformService = $azure_storage_queue_webform_service;
    return $this;
  }

  /**
   * Sets queue service.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue service.
   *
   * @return $this
   */
  protected function setAzureStorageQueue(QueueFactory $queue_factory): static {
    $this->azureStorageQueue = $queue_factory->get('wateraid_azure_storage_queue');
    return $this;
  }

  /**
   * Sets the logger interface.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   A logger instance.
   *
   * @return $this
   */
  protected function setLogger(LoggerChannelInterface $logger): static {
    $this->logger = $logger;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    MessageSubmissionDataHelper::buildForm($form, $form_state, $this->configuration, $this->getWebform());
    MessageMetadataHelper::buildForm($form, $form_state, $this->configuration);
    CloneHandlerDataHelper::buildForm($form, $form_state, $this->configuration, $this->getWebform());
    ConnectionHelper::buildForm($form, $form_state, $this->configuration);

    $form['cron'] = [
      '#type' => 'details',
      '#title' => $this->t('Cron settings'),
    ];

    $form['cron']['queue_retry'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Retry Messages via cron'),
      '#description' => $this->t('Populates and processes the internal retry queue.'),
      '#default_value' => $this->configuration['cron']['queue_retry'] ?? FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);

    // Validate if "queue_name" is not empty.
    if (empty($form_state->getValue([ConnectionHelper::FIELD, 'queue_name']))) {
      $form_state->setErrorByName('connection][queue_name', $this->t('Queue name cannot be left empty.'));
    }

    // Do queue mode validation if applicable.
    if ((bool) $form_state->getValue([ConnectionHelper::FIELD, 'queue_mode_bypass']) !== TRUE) {
      try {
        $this->azureStorageQueueWebformService->queueModeValidate($form_state->getValue([
          ConnectionHelper::FIELD,
          'queue_name',
        ]));
      }
      catch (InvalidEnvironmentException $e) {
        $form_state->setErrorByName('connection][queue_name', $e->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration[MessageSubmissionDataHelper::FIELD] = $form_state->getValue(MessageSubmissionDataHelper::FIELD)['included_columns'] ?? [];
    $this->configuration[MessageMetadataHelper::FIELD] = $form_state->getValue(MessageMetadataHelper::FIELD)['map'] ?? [];
    $this->configuration['protocol'] = $form_state->getValue([ConnectionHelper::FIELD, 'protocol']) ?: NULL;
    $this->configuration['account_name'] = $form_state->getValue([ConnectionHelper::FIELD, 'account_name']) ?: NULL;
    $this->configuration['test_account_key'] = $form_state->getValue([ConnectionHelper::FIELD, 'test_account_key']) ?: NULL;
    $this->configuration['live_account_key'] = $form_state->getValue([ConnectionHelper::FIELD, 'live_account_key']) ?: NULL;
    $this->configuration['endpoint_suffix'] = $form_state->getValue([ConnectionHelper::FIELD, 'endpoint_suffix']) ?: NULL;
    $this->configuration['queue_name'] = $form_state->getValue([ConnectionHelper::FIELD, 'queue_name']) ?: NULL;
    $this->configuration['queue_mode_bypass'] = (bool) $form_state->getValue([
      ConnectionHelper::FIELD,
      'queue_mode_bypass',
    ]);
    $this->configuration['cron']['queue_retry'] = (bool) $form_state->getValue(['cron', 'queue_retry']);
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary(): array {
    $configuration = $this->getConfiguration();
    $settings = $configuration['settings'];
    $items = [];
    $items[] = $this->t('<strong>Handler:</strong> @status', [
      '@status' => $this->isEnabled() ? '✓' : '✗',
    ]);
    $items[] = $this->t('<strong>Cron:</strong> @status', [
      '@status' => $settings['cron']['queue_retry'] ?? FALSE ? '✓' : '✗',
    ]);
    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE): void {
    // Do not allow more than 1 POST per submission to Azure in the handler.
    if ($update === FALSE) {
      try {
        $this->azureStorageQueueWebformService->postWebformSubmission($webform_submission);
      }
      catch (\Exception $e) {
        // All exceptions to be logged.
        $this->logger->error('Submission ID: %sid, Error: %error', [
          '%sid' => $webform_submission->id(),
          '%error' => $e->getMessage(),
        ]);

        // Don't attempt retries for Exceptions that are purposely being
        // silenced (see MES-17).
        if ($e instanceof BaseAzureStorageQueueException && $e->isSilenced() === TRUE) {
          return;
        }

        // Create a new message only containing the Webform Submission ID.
        $message = (new AzureStorageQueueMessage)->setId($webform_submission->id());

        // Add to internal queue for re-attempt on cron.
        if ($item_id = $this->azureStorageQueue->createItem($message)) {
          $this->logger->info('Submission ID: %sid queued for retry with Queue Item ID %item_id', [
            '%sid' => $webform_submission->id(),
            '%item_id' => $item_id,
          ]);
        }
      }
    }
  }

}
