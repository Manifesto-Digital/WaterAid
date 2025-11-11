<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\process;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateStubInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\redirect\Entity\Redirect;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a redirect_migration_lookup plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: redirect_migration_lookup
 *     source: foo
 *     migration:
 *       - migration1
 *       - migration2
 * @endcode
 *
 * @MigrateProcessPlugin(id = "redirect_migration_lookup")
 */
final class RedirectMigrationLookup extends MigrationLookup implements ContainerFactoryPluginInterface {

  /**
   * Constructs a MigrationLookup object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The Migration the plugin is being used in.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   * @param \Drupal\migrate\MigrateStubInterface $migrate_stub
   *   The migrate stub service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    MigrateLookupInterface $migrate_lookup,
    MigrateStubInterface $migrate_stub,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $migrate_lookup, $migrate_stub);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('migrate.lookup'),
      $container->get('migrate.stub'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): mixed {

    // If we don't have a value, there's nothing to do.
    if (!$value) {
      return NULL;
    }

    // If this is a node link, though, we need to update the entity id.
    if (str_starts_with($value, 'internal:/node/')) {
      $updated = FALSE;

      if ($nid = parent::transform(substr($value, 15), $migrate_executable, $row, $destination_property)) {

        // We visitors to view content within the context of the group it is in,
        // so we the redirect to point to the group relationship, NOT the actual
        // node.
        /** @var \Drupal\group\Entity\GroupInterface $group */
        if ($group = $this->entityTypeManager->getStorage('group')->load(1)) {

          /** @var \Drupal\node\NodeInterface $node */
          if ($node = $this->entityTypeManager->getStorage('node')->load($nid)) {
            if ($relationships = $group->getRelationshipsByEntity($node)) {

              // Each node can only bhe added to one group, so this should only
              // have one relationship.
              /** @var \Drupal\group\Entity\GroupRelationshipInterface $relationship */
              if ($relationship = reset($relationships)) {
                $value = 'internal:/' . $relationship->getEntityTypeId() . '/' . $relationship->id();
                $updated = TRUE;
              }
            }
          }
        }
      }

      if (!$updated) {

        // If it is an internal link with a node id we don't recognise, a
        // redirect using it will 404 so return a NULL value.
        return NULL;
      }
    }

    return $value;
  }

}
