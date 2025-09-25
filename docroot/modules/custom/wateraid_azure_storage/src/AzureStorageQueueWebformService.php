<?php

namespace Drupal\wateraid_azure_storage;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\azure_storage\AzureStorage;
use Drupal\azure_storage\AzureStorageClientInterface;
use Drupal\wateraid_azure_storage\Exception\DisabledCronException;
use Drupal\wateraid_azure_storage\Exception\DisabledHandlerException;
use Drupal\wateraid_azure_storage\Exception\InvalidEnvironmentException;
use Drupal\webform\WebformSubmissionInterface;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageOptions;

/**
 * The Webform Service.
 *
 * @package Drupal\wateraid_azure_storage
 */
class AzureStorageQueueWebformService implements AzureStorageQueueWebformServiceInterface {

  /**
   * Fixed TTL of 1 year in seconds (60 * 60 * 24 * 7 * 52)
   *
   * @var int
   */
  public const AZURE_STORAGE_QUEUE_MESSAGE_TTL = 31449600;

  /**
   * Azure Storage Client service.
   */
  protected AzureStorageClientInterface $azureStorageClient;

  /**
   * Logger service.
   */
  protected LoggerChannelInterface $logger;

  /**
   * The Azure Storage settings.
   */
  protected Config $config;

  /**
   * The Azure Storage message builder service.
   */
  protected AzureStorageQueueMessageBuilderInterface $messageBuilder;

  /**
   * AzureStorageQueueWebformService constructor.
   *
   * @param \Drupal\azure_storage\AzureStorageClientInterface $azure_storage_client
   *   Azure Storage Client.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory instance.
   * @param \Drupal\wateraid_azure_storage\AzureStorageQueueMessageBuilderInterface $message_builder
   *   A message builder.
   */
  public function __construct(AzureStorageClientInterface $azure_storage_client, LoggerChannelInterface $logger, ConfigFactoryInterface $config_factory, AzureStorageQueueMessageBuilderInterface $message_builder) {
    $this->azureStorageClient = $azure_storage_client;
    $this->logger = $logger;
    $this->config = $config_factory->get('azure_storage.settings');
    $this->messageBuilder = $message_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function postWebformSubmission(WebformSubmissionInterface $webform_submission, $process_type = 'initial'): void {

    // Do not proceed if we already have an Azure Message ID captured and when
    // not manual re-attempted. This is to prevent duplicates sent across.
    if ($process_type !== 'manual' && $webform_submission->get('message_id')->getValue()) {
      return;
    }

    $handlers = $webform_submission->getWebform()->getHandlers('wateraid_azure_storage_queue');
    if ($handlers->count() === 0) {
      throw new DisabledHandlerException('Webform has no Azure Storage Queue handler.');
    }

    /** @var \Drupal\webform\Plugin\WebformHandlerInterface $handler */
    $handler = $handlers->getIterator()->current();
    $configuration = $handler->getConfiguration();
    $settings = $configuration['settings'] ?? [];
    $queue_retry = (bool) ($settings['cron']['queue_retry'] ?? FALSE);
    $queue_mode_bypass = (bool) ($settings['queue_mode_bypass'] ?? FALSE);

    if ($process_type !== 'queue' && $handler->isDisabled()) {
      throw new DisabledHandlerException('Webform Azure Storage Queue handler is disabled.');
    }

    if ($process_type === 'queue' && $queue_retry === FALSE) {
      throw new DisabledCronException('Webform Azure Storage Queue cron retries are disabled.');
    }

    // Do queue mode validation if applicable.
    if ($queue_mode_bypass !== TRUE) {
      $this->queueModeValidate($settings['queue_name']);
    }

    // Extract "Account Key" as environment aware per the base connector module.
    $mode = $this->config->get('mode') ?? 'test';
    $config_key = $mode . '_account_key';

    // We require an explicit connection string specification for Webforms!
    $connection_string = $this->azureStorageClient->getStorageQueueConnectionString([
      'protocol' => $settings['protocol'] ?? NULL,
      'account_name' => $settings['account_name'] ?? NULL,
      'account_key' => $settings[$config_key] ? AzureStorage::getAccountKey($settings[$config_key]) : NULL,
      'endpoint_suffix' => $settings['endpoint_suffix'] ?? NULL,
    ]);

    // Set connection string and get queue service.
    $storage_queue_service = $this->azureStorageClient
      ->setStorageQueueService($connection_string)
      ->getStorageQueueService();

    $message = $this->messageBuilder->create($webform_submission, $configuration);

    // Fixed TTL of 1 year in seconds.
    $message_options = new CreateMessageOptions();
    $message_options->setTimeToLiveInSeconds(self::AZURE_STORAGE_QUEUE_MESSAGE_TTL);

    // Add message to the queue.
    try {
      $message_result = $storage_queue_service->createMessage($settings['queue_name'], $message->getData(), $message_options);
      $queue_message = $message_result->getQueueMessage();
    }
    catch (ServiceException $e) {
      // Propagate to origin.
      throw $e;
    }

    // Resave changes to the submission data without invoking any hooks
    // or handlers.
    $webform_submission
      ->set('message_id', $queue_message->getMessageId(), FALSE)
      // ->set('pop_receipt', $queue_message->getPopReceipt(), FALSE)
      ->resave();

    $this->logger->info('Submission ID: %sid queued to Azure Storage Queue with Message ID %msg_id and Pop Receipt %pop_receipt', [
      '%sid' => $webform_submission->id(),
      '%msg_id' => $queue_message->getMessageId(),
      '%pop_receipt' => $queue_message->getPopReceipt(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function queueModeValidate($queue_name): void {

    $needles = ['prod', 'live', 'test'];
    $queue_name = mb_strtolower($queue_name);
    $match = str_replace($needles, '', $queue_name) !== $queue_name;

    if ($match === FALSE) {
      // No need to validate if no "mode identifier" is present.
      return;
    }

    // Restrict "prod" or "live" queue mode from being run on non-Production.
    if ($this->getEnvMode() === 'test' && (str_contains($queue_name, 'prod') || str_contains($queue_name, 'live'))) {
      throw new InvalidEnvironmentException('You cannot use the "prod" or "live" queue mode identifier on a non-Production environment.');
    }

    // Restrict "test" queue mode from being run on Production.
    if ($this->getEnvMode() === 'live' && str_contains($queue_name, 'test')) {
      throw new InvalidEnvironmentException('You cannot use the "test" queue mode identifier on a Production environment.');
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo Currently we can't unfortunately take "$mode === 'live'" into
   *   account for consideration as the Account Key is equal across envs.
   *   Instead, use ::getEnvMode() for now.
   */
  public function getMode(): string {
    // Validate on both configured mode and current AC / ACSF environment.
    $mode = $this->config->get('mode') ?? 'test';
    $env = $this->getEnvMode();
    // Be very explicit on what is considered "live" mode.
    if ($mode === 'live' || $env === 'live') {
      return 'live';
    }
    return 'test';
  }

  /**
   * {@inheritdoc}
   */
  public function getEnvMode(): string {
    $ah_env = $_ENV['AH_SITE_ENVIRONMENT'] ?? NULL;
    return $ah_env === 'prod' || $ah_env === '01live' ? 'live' : 'test';
  }

}
