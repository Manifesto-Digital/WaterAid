<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'wa_orange_dam_usage_processor' queue worker.
 *
 * @QueueWorker(
 *   id = "wa_orange_dam_usage_processor",
 *   title = @Translation("Usage Processor"),
 *   cron = {"time" = 60},
 * )
 */
final class UsageProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new UsageProcessor instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly Connection $connection,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannel $logger,
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
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.wa_orange_dam'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (isset($data->source_id) && isset($data->source_type)) {
      $entity = NULL;

      try {
        $entity = $this->entityTypeManager->getStorage($data->source_type)->load($data->source_id);
      }
      catch (\Exception $e) {
        $this->logger->error($this->t('Error attempting to load :type (:id) entity linked to expiring media: - :error', [
          ':type' => $data->source_type,
          ':id' => $data->source_id,
          ':error' => $e->getMessage(),
        ]));
      }

      // We need the node that is using this media item.
      while ($entity instanceof ParagraphInterface) {
        $entity = $entity->getParentEntity();
      }

      if ($entity instanceof NodeInterface) {
        $this->connection->merge('wa_orange_dam')
          ->key('uid', $entity->getOwnerId())
          ->keys([
            'uid' => $entity->getOwnerId(),
            'mid' => $data->entity_id,
            'nid' =>  $entity->id(),
          ])
          ->fields([
            'uid' => $entity->getOwnerId(),
            'mid' => $data->entity_id,
            'nid' => $entity->id(),
            'expiry_date' => $data->field_dam_expiry_date_value,
          ])
          ->execute();
      }
    }
  }

}
