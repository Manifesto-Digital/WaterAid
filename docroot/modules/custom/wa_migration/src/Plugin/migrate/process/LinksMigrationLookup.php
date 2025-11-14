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
 * Provides a links_migration_lookup plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: links_migration_lookup
 *     source: foo
 *     migration:
 *       - migration1
 *       - migration2
 * @endcode
 *
 * @MigrateProcessPlugin(id = "links_migration_lookup")
 */
final class LinksMigrationLookup extends MigrationLookup implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): mixed {

    // If we don't have a value, there's nothing to do.
    if (!$value) {
      return NULL;
    }

    $link = [];

    if ($value[0]) {
      if (str_starts_with($value[0], 'entity:node/') || str_starts_with($value[0], 'internal:node/')) {
        $nid = (str_starts_with($value[0], 'entity:node/')) ? substr($value[0], 12) : substr($value[0], 15);

        if ($new_nid = parent::transform($nid, $migrate_executable, $row, $destination_property)) {
          $link['url'] = 'internal:node/' . $new_nid;
          $link['title'] = $value[1];
        }
      }
      else {
        $link['url'] = $value[0];
        $link['title'] = $value[1];
      }
    }

    return ($link) ?? NULL;
  }

}
