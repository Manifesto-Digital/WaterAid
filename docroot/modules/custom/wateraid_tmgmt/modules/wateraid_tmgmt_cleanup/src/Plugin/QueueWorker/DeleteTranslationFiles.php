<?php

namespace Drupal\wateraid_tmgmt_cleanup\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The queue worker for deleting old translation files.
 *
 * @QueueWorker(
 *   id = "wateraid_tmgmt_cleanup_delete_translation_files",
 *   title = @Translation("Deletes old tmgmt translation files."),
 *   cron = {"time" = 30}
 * )
 */
final class DeleteTranslationFiles extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The 'entity_type.manager' service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $worker = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $worker->entityTypeManager = $container->get('entity_type.manager');
    return $worker;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($file = $this->getEntityTypeManager()->getStorage('file')->load($data)) {
      $file->delete();
    }
  }

  /**
   * Get 'entity_type.manager' service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The 'entity_type.manager' service.
   */
  protected function getEntityTypeManager(): EntityTypeManagerInterface {
    return $this->entityTypeManager;
  }

}
