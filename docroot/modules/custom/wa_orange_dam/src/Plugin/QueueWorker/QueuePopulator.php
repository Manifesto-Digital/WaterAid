<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'wa_orange_dam_queue_populator' queue worker.
 *
 * @QueueWorker(
 *   id = "wa_orange_dam_queue_populator",
 *   title = @Translation("Queue Populator"),
 *   cron = {"time" = 60},
 * )
 */
final class QueuePopulator extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new QueuePopulator instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly QueueFactory $queue,
    private readonly Connection $connection,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('queue'),
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $query = NULL;

    if ($data == 'wa_orange_dam_expiry_checker') {
      $query = $this->connection->select('media_field_data', 'm')
        ->fields('m', ['mid'])
        ->condition('m.bundle', [
          'dam_file',
          'dam_image',
          'dam_video',
        ], 'IN');
      $query->leftJoin('media__field_dam_expired', 'e', 'e.entity_id = m.mid');

      $or = $query->orConditionGroup();
      $or->condition('e.field_dam_expired_value', 1, '<>');
      $or->isNull('e.field_dam_expired_value');

      $query->condition($or);
      $query->leftJoin('media__field_dam_last_checked', 'l', 'l.entity_id = m.mid');
      $query->orderBy('l.field_dam_last_checked_value');
    }
    elseif ($data == 'wa_orange_dam_usage_processor') {
      $query = $this->connection->select('media__field_dam_expiry_date', 'e')
        ->condition('field_dam_expiry_date_value', strtotime('now +3 months'), '<');
      $query->leftJoin('entity_usage', 'u', 'u.target_id = e.entity_id AND u.target_type = :type', [
        ':type' => 'media',
      ]);
      $query->leftJoin('entity_usage', 'pu', 'pu.target_id = u.source_id AND pu.target_type = u.source_type');
      $query->fields('e', ['entity_id', 'field_dam_expiry_date_value']);
      $query->fields('u', ['source_id', 'source_type']);
    }

    if ($query) {
      $queue = $this->queue->get($data);

      $result = 'placeholder';
      $start = 0;

      while (!empty($result)) {
        $query->range($start, 100);
        $result = $query->execute()->fetchAll();

        foreach ($result as $item) {
          $queue->createItem($item);
        }

        $start += 100;
      }
    }
  }

}
