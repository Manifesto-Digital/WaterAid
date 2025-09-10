<?php

namespace Drupal\wateraid_azure_storage\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\wateraid_azure_storage\AzureStorageQueueErrorLogEntry;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Drush command for the WaterAid Azure Storage module.
 *
 * @package Drupal\wateraid_azure_storage\Commands
 */
class WaterAidAzureStorageCommands extends DrushCommands {

  /**
   * The date formatter service.
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * The mail manager.
   */
  protected MailManagerInterface $mailManager;

  /**
   * The settings config.
   */
  protected ImmutableConfig $configSettings;

  /**
   * Queue interface.
   */
  protected QueueFactory $queueFactory;

  /**
   * Queue interface.
   */
  protected QueueInterface $azureStorageErrorNotifyQueue;

  /**
   * WaterAidAzureStorageCommands constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The Queue Factory.
   */
  public function __construct(DateFormatterInterface $date_formatter, MailManagerInterface $mail_manager, ConfigFactoryInterface $config_factory, QueueFactory $queue_factory) {
    parent::__construct();
    $this->dateFormatter = $date_formatter;
    $this->mailManager = $mail_manager;
    $this->configSettings = $config_factory->get('wateraid_azure_storage.settings');
    $this->queueFactory = $queue_factory;
    $this->azureStorageErrorNotifyQueue = $queue_factory->get('wateraid_azure_storage_error_notify_queue');
  }

  /**
   * SQL sanitise options hook.
   *
   * @param mixed $options
   *   Options to use.
   *
   * @hook option sql-sanitize
   * @option sanitize-azure-storage-queues
   *     By default, queues are truncated. Specify 'no' to disable that.
   *  /
   */
  public function options(mixed $options = ['sanitize-azure-storage-queues' => NULL]): void {}

  /**
   * SQL sanitise on-event hook.
   *
   * @param mixed[] $messages
   *   An array of messages.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input interface.
   *
   * @hook on-event sql-sanitize-confirms
   */
  public function messages(array &$messages, InputInterface $input): void {
    $options = $input->getOptions();
    if ($options['sanitize-azure-storage-queues'] !== 'no') {
      $messages[] = dt('Truncate Azure Storage Queue items.');
    }
  }

  /**
   * Sanitise Azure Storage Queues from the database.
   *
   * @hook post-command sql-sanitize
   */
  public function sanitize(mixed $result, CommandData $command_data): void {
    $options = $command_data->options();
    if ($options['sanitize-azure-storage-queues'] !== 'no') {
      $this->queueFactory->get('wateraid_azure_storage_queue')->deleteQueue();
      $this->queueFactory->get('wateraid_azure_storage_error_notify_queue')->deleteQueue();
      $this->logger()->success(dt('Azure Storage Queue items truncated.'));
    }
  }

  /**
   * Drush command to forward Azure Storage failures to a mailbox.
   *
   * @param int $time_limit
   *   Limit in seconds this command is allowed to run for.
   *
   * @command wateraid:azure-storage:notify
   * @aliases wa-asn
   * @usage wateraid:azure-storage:notify 3600
   *
   * @throws \Exception
   *   When no mailbox has been configured.
   */
  public function notifyReport(int $time_limit = 60): void {

    $start = microtime(TRUE);
    $end = time() + $time_limit;
    $error_notify = (bool) ($this->configSettings->get('error_notify') ?: FALSE);
    $error_mail = $this->configSettings->get('error_mail') ?: NULL;

    if ($error_notify === FALSE) {
      throw new \Exception(dt('Error notifications are disabled.'));
    }

    if ($error_mail === NULL) {
      throw new \Exception(dt('The error recipient has not been configured.'));
    }

    $body = '';
    $item_count = 0;

    while ((!$time_limit || time() < $end) && ($item = $this->azureStorageErrorNotifyQueue->claimItem())) {
      if ($item->data instanceof AzureStorageQueueErrorLogEntry) {
        $error_log_entry = $item->data;
        $body .= $this->dateFormatter->format($error_log_entry->getTimestamp(), 'short') . ': ' . $error_log_entry->getMessage() . PHP_EOL;
        $item_count++;
      }

      $this->azureStorageErrorNotifyQueue->deleteItem($item);
    }

    $elapsed = microtime(TRUE) - $start;

    // Delete the queue, so we never get left with overhead.
    $this->azureStorageErrorNotifyQueue->deleteQueue();

    if ($item_count === 0) {
      $this->output()->writeln(dt('No errors to report.'));
      return;
    }

    $body = 'Summary: ' . $item_count . ' error(s) logged:' . PHP_EOL . $body;
    $body .= 'Report generated in ' . $elapsed . 'ms';

    $params = [
      'body' => $body,
    ];

    $this->mailManager->mail('wateraid_azure_storage', 'notify_report', $error_mail, 'en', $params);
    $this->output()->writeln(dt('Error report sent!'));
  }

}
