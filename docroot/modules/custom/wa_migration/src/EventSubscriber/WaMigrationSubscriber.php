<?php

declare(strict_types=1);

namespace Drupal\wa_migration\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @todo Add description for this subscriber.
 */
final class WaMigrationSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a WaMigrationSubscriber object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Check for the image server status just once to avoid thousands of requests.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The import event object.
   */
  public function onMigratePostRowSave(MigratePostRowSaveEvent $event): void {
    $migration = $event->getMigration()->getPluginDefinition();
    if (isset($migration['migration_group']) && $migration['migration_group'] == 'wateraid_uk_nodes') {
      if ($values = $event->getDestinationIdValues()) {
        if ($nid = $values[0]) {

          /** @var \Drupal\node\NodeInterface $node */
          if ($node = $this->entityTypeManager->getStorage('node')->load($nid)) {
            /** @var \Drupal\group\Entity\GroupInterface $group */
            foreach ($this->entityTypeManager->getStorage('group')->loadMultiple() as $group) {
              if ($group->bundle() == 'wateraid_site' && $group->label() == 'WaterAid UK') {
                $type = $node->bundle();
                $group->addRelationship($node, 'group_node:' . $type, ['uid' => 1]);
              }
            }
          }
        }
      }
    }
  }

  public function onMigratePreRowSave(MigratePreRowSaveEvent $event): void {

    // We only need to do this for redirects.
    if ($event->getMigration()->id() == 'redirect') {

      // Create the redirect entity so we can check whether it is a duplicate.
      $storage = $this->entityTypeManager->getStorage('redirect');

      /** @var \Drupal\redirect\Entity\Redirect $redirect */
      $redirect = $storage->create($event->getRow()->getDestination());
      $redirect->preSave($storage);

      // The hash is calculated in the presave function, so call that.
      $hash = $redirect->getHash();

      $existing = $storage->loadByProperties([
        'hash' => $hash,
      ]);

      if (!empty($existing)) {
        foreach ($existing as $entity) {

          // Delete the entity so the duplicate can be saved without issue.
          $entity->delete();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MigrateEvents::POST_ROW_SAVE => ['onMigratePostRowSave'],
      MigrateEvents::PRE_ROW_SAVE => ['onMigratePreRowSave'],
    ];
  }

}
