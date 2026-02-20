<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'wa_orange_dam_notification_populator' queue worker.
 *
 * @QueueWorker(
 *   id = "wa_orange_dam_notification_populator",
 *   title = @Translation("Notification Populator"),
 *   cron = {"time" = 60},
 * )
 */
final class NotificationPopulator extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new NotificationPopulator instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly QueueFactory $queue,
    private readonly Connection $connection,
    private readonly EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {

    // Only send the emails if the usage queue has stopped processing.
    if ($queue = $this->queue->get('wa_orange_dam_usage_processor')) {
      if (empty($queue->numberOfItems())) {

        $mail_queue = $this->queue->get('wa_orange_dam_send_mail');

        // Queue the systems admin email,
        foreach ($this->entityTypeManager->getStorage('user')->getQuery()
          ->condition('status', 1)
          ->condition('roles', 'wateraid_super_admin')
          ->accessCheck(FALSE)
          ->execute() as $uid) {
          $mail_queue->createItem(['type' => 'systems_admin', 'uid' => $uid]);
        }

        // And the node author emails.
        foreach ($this->connection->select('wa_orange_dam', 'o')
          ->fields('o', ['uid'])
          ->condition('uid', 0, '>')
          ->groupBy('uid')
          ->execute()->fetchAll() as $result) {
          $mail_queue->createItem(['type' => 'node_author', 'uid' => $result->uid]);
        }
      }
      else {

        // Requeue so the emails send when the data is fully populated.
        $queue = $this->queue->get('wa_orange_dam_notification_populator');

        // We don't want multiple versions of this queue running so only requeue
        // if the queue is empty, bearing in mind this run hasn't finished yet
        // so will still be in the system.
        if ($queue->numberOfItems() < 2) {
          $this->queue->get('wa_orange_dam_notification_populator')->createItem($data);
        }
      }
    }
  }

}
