<?php

namespace Drupal\azure_blob_storage\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Database\StatementInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class AzureBlobStorageCommands extends DrushCommands {

  /**
   * Constructs an AzureBlobStorageCommands object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly QueueInterface $queue,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('queue')->get('azure_blob_storage_queue')
    );
  }

  /**
   * Get the ids of all the available webforms.
   *
   * @return array
   *   An array of webform IDs.
   */
  public function getWebformIds(): array {
    $database = \Drupal::database();
    $result   = $database->select('webform', 'ws')
      ->fields('ws', ['webform_id'])
      ->execute();

    $ids = [];

    foreach ($result as $record) {
      $ids[] = $record->webform_id;
    }

    return $ids;
  }

  /**
   * Get all the submissions for the given webform ids.
   *
   * @param array $webformIds
   *   An array of webform IDs to get the associated submissions for.
   *   If an empty array is provided all submissions will be retrieved.
   *
   * @return StatementInterface|null
   */
  public function getWebformSubmissions(array $webformIds): ?StatementInterface {
    $database = \Drupal::database();
    $query    = $database->select('webform_submission', 'ws');
    $query->fields('ws', ['webform_id', 'sid']);


    if (!empty($webformIds)) {
      $query->condition('webform_id', $webformIds, 'IN');
    }

    return $query->execute();
  }

  /**
   * Get all the donation webform IDs.
   *
   * @return array
   *   An array webform IDs.
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function getDonationWebformIds(): array {
    $webforms = $this->entityTypeManager->getStorage('webform')
      ->loadMultiple($this->getWebformIds());

    $webformIds = [];

    foreach ($webforms as $webform) {
      try {
        $webform->getHandler("wateraid_donations");
        $webformIds[] = $webform->id();
      }
      catch (\Exception $e) {

      }
    }

    return $webformIds;
  }

  /**
   * Get the set of webform submissions based on the criteria provided.
   *
   * @param string|null $id
   *   The webform ID to retrieve the submissions for
   * @param bool $donationOnly
   *   A boolean indicating if only donation form submissions should be retrieved.
   * @return void
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function submissionsGet(string $id = null, bool $donationOnly = false): void {
    $webformIds = [];

    if (!empty($id)) {
      $webformIds = [$id];
    }
    else if ($donationOnly) {
      $webformIds = $this->getDonationWebformIds();
    }

    $this->logger()->notice(print_r($webformIds, 1));
    $submissions = $this->getWebformSubmissions($webformIds);
    $count       = 0;

    foreach ($submissions as $submission) {
      $item = [
        'webform_id' => $submission->webform_id,
        'sid'        => $submission->sid,
        'tries'      => 0,
      ];

      $this->queue->createItem($item);

      $count++;
    }

    $this->logger()->success(dt("$count items successfully enqueued"));
  }


  /**
   * Command description here.
   */
  #[CLI\Command(name: 'azure_blob_storage:enqueue', aliases: ['abs'])]
  #[CLI\Argument(name: 'webform', description: 'Enqueue submissions for a specific webform')]
  #[CLI\Option(name: 'donation-only', description: 'Enqueue donation forms only')]
  #[CLI\Option(name: 'all', description: 'Enqueue all form submissions')]
  #[CLI\Usage(name: 'azure_blob_storage:enqueue 29', description: 'Usage description')]
  public function enqueue($webform = "", $options = [])
  {
    if (empty($webform)) {
      if (!empty($options['donation-only'])) {
        $this->submissionsGet(null, true);
      } else if (!empty($options['all'])) {
        $this->submissionsGet();
      } else {
        $this->logger()->error(dt('Please provide a webform id or specify -all.'));
      }
    } else {
      $this->submissionsGet($webform);
    }

  }
}
