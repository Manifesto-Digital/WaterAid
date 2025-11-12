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
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a link_migration_lookup plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: link_migration_lookup
 *     source: foo
 *     migrations:
 *       - migration1
 *       - migration2
 * @endcode
 *
 * @MigrateProcessPlugin(id = "link_migration_lookup")
 */
final class LinkMigrationLookup extends MigrationLookup implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): mixed {

    // If we don't have a value, there's nothing to do.
    if (!$value) {
      return NULL;
    }

    // If the link is external, we can just use that as a value.
    if (UrlHelper::isExternal($value)) {
      return $value;
    }

    // If this is a node link, though, we need to update the entity id.
    if (str_starts_with($value, 'entity:node/')) {
      if ($nid = parent::transform(substr($value, 12), $migrate_executable, $row, $destination_property)) {
        $value = 'entity:node/' . $nid;
      }
    }

    return $value;
  }

}
