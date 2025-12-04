<?php

declare(strict_types=1);

namespace Drupal\wa_migration\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Event\MigrateRowDeleteEvent;
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
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onMigratePostRowSave(MigratePostRowSaveEvent $event): void {
    $migration = $event->getMigration()->getPluginDefinition();
    if (isset($migration['migration_group']) && str_ends_with($migration['migration_group'], '_nodes')) {
      $matches = [];
      preg_match('/wateraid_(.+)_nodes/', $migration['migration_group'], $matches);

      if ($matches[1]) {

        $groups = [
          'bd' => 'Bangladesh',
          'et' => 'Ethiopia',
          'gh' => 'Ghana',
          'global' => 'Global',
          'mw' => 'Malawi',
          'mz' => 'Mozambique',
          'ng' => '	Nigeria',
          'pk' => '	Pakistan',
          'tz' => 'Tanzania',
          'ug' => 'Uganda',
          'uk' => 'WaterAid UK',
        ];

        $group_label = (array_key_exists($matches[1], $groups)) ? $groups[$matches[1]] : NULL;

        if ($group_label && $values = $event->getDestinationIdValues()) {
          if ($nid = $values[0]) {
            /** @var \Drupal\node\NodeInterface $node */
            if ($node = $this->entityTypeManager->getStorage('node')
              ->load($nid)) {
              /** @var \Drupal\group\Entity\GroupInterface $group */
              foreach ($this->entityTypeManager->getStorage('group')
                ->loadByProperties([
                  'label' => $group_label,
                ]) as $group) {
                if ($group->bundle() == 'wateraid_site') {
                  $type = $node->bundle();
                  $group->addRelationship($node, 'group_node:' . $type, ['uid' => 1]);
                }
              }

              if ($event->getMigration()->id() == 'press_and_media_' . $matches[1] || $event->getMigration()->id() == 'press_and_media') {
                if ($node->hasField('field_get_involved')) {
                  $values = [];

                  // Handle any existing values.
                  if ($existing = $node->get('field_get_involved')
                    ->getValue()) {
                    foreach ($existing[0] as $item) {
                      $values[] = ['target_id' => $item];
                    }
                  }

                  /** @var \Drupal\taxonomy\TermInterface $term */
                  $terms = $this->entityTypeManager->getStorage('taxonomy_term')
                    ->loadByProperties([
                      'vid' => 'get_involved',
                      'name' => 'Press Release',
                    ]);
                  $term = reset($terms);

                  $values[] = ['target_id' => $term->id()];
                  $node->set('field_get_involved', $values);
                  $node->save();
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Delete the group relationship on rollback.
   *
   * @param \Drupal\migrate\Event\MigrateRowDeleteEvent $event
   *   The event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onMigratePreRowDelete(MigrateRowDeleteEvent $event): void {
    $migration = $event->getMigration()->getPluginDefinition();
    if (isset($migration['migration_group']) && $migration['migration_group'] == 'wateraid_uk_nodes' && $migration['id'] !== 'redirect') {
      $storage = $this->entityTypeManager->getStorage('node');
      foreach ($event->getDestinationIdValues() as $id) {

        /** @var \Drupal\node\NodeInterface $node */
        if ($node = $storage->load($id)) {

          /** @var \Drupal\group\Entity\GroupRelationshipInterface $relationship */
          foreach (GroupRelationship::loadByEntity($node) as $relationship) {
            $relationship->delete();
          }
        }
      }
    }
  }

  /**
   * Prevent duplicate redirects being created.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   The event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onMigratePreRowSave(MigratePreRowSaveEvent $event): void {

    // We only need to do this for redirects.
    if (str_starts_with($event->getMigration()->id(), 'redirect')) {

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
      MigrateEvents::PRE_ROW_DELETE => ['onMigratePreRowDelete'],
    ];
  }

}
