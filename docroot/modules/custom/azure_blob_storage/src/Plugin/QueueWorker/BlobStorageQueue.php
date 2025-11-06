<?php

declare(strict_types=1);

namespace Drupal\azure_blob_storage\Plugin\QueueWorker;

use Drupal\azure_blob_storage\service\AzureApi;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'azure_blob_storage_queue' queue worker.
 *
 * @QueueWorker(
 *   id = "azure_blob_storage_queue",
 *   title = @Translation("Blob Storage Queue"),
 *   cron = {"time" = 60},
 * )
 */
final class BlobStorageQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new BlobStorageQueue instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AzureApi $azureBlobStorageApi,
    private readonly LoggerChannelInterface $loggerChannel,
    private readonly QueueInterface $queue,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (Settings::get('azure_blob_storage_accountname')) {
      $this->azureBlobStorageApi->setAccountName(Settings::get('azure_blob_storage_accountname'));
    }

    if (Settings::get('azure_blob_storage_container')) {
      $this->azureBlobStorageApi->setContainer(Settings::get('azure_blob_storage_container'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('azure_blob_storage.api'),
      $container->get('logger.channel.azure_blob_storage'),
      $container->get('queue')->get('azure_blob_storage_queue'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (!isset($data['webform_id']) || !isset($data['sid'])) {
      return;
    }

    // If a number of tries has been provided, use it. If not, assume this is
    // the first try because our code will definitely add the number.
    $tries = $data['tries'] ?? 0;

    // If we've had five tries, we'll give up.
    if ($tries >= 5) {
      $this->loggerChannel->critical($this->t('Error sending submission :sid from the :webform webform to the Azure storage blob', [
        ':sid' => $data['sid'],
        ':webform' => $data['webform_id'],
      ]));
      return;
    }

    $error = FALSE;

    /** @var \Drupal\webform\WebformSubmissionInterface $submission */
    if ($submissions = $this->entityTypeManager->getStorage('webform_submission')
      ->loadByProperties([
        'sid' => $data['sid'],
        'webform_id' => $data['webform_id'],
      ])) {
      if (count($submissions) == 1) {
        $submission = reset($submissions);
        $name = $data['webform_id'] . '-' . $submission->uuid() . '.json';

        if ($this->azureBlobStorageApi->blobPut($name, $this->generateBlobArray($submission), TRUE)) {
          // The submission has been successfully stored in the blob, so we can
          // delete it from the website.
//          $submission->delete();
        }
        else {
          $error = TRUE;
        }
      }
      else {
        $error = TRUE;
      }
    }
    else {
      $error = TRUE;
    }

    if ($error) {

      // If something went wrong, we'll push the data back into the queue to
      // try again.
      $tries++;
      $data['tries'] = $tries;

      $this->queue->createItem($data);
    }
  }

  /**
   * Generates the data structure to be stored in Azure.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The webform submission.
   *
   * @return array
   *   A structured array of data.
   */
  private function generateBlobArray(WebformSubmissionInterface $submission): array {
    $webform = $submission->getWebform();
    $owner = $webform->getOwner();

    $date = ($submitted = $submission->getCompletedTime()) ? DrupalDateTime::createFromTimestamp($submitted) : new DrupalDateTime();

    return [
      'id' => $submission->uuid(),
      'webform' => $webform->id(),
      'webform_owner' => ($owner) ? $owner->label() : 'Anonymous',
      'webform_last_updated' => '',
      'submission_data' => $submission->getData(),
      'submission_date' => $date->format(\DateTimeInterface::ATOM),
    ];
  }

}
