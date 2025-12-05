<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;

/**
 * The 'landing_node_source' source plugin.
 *
 * @MigrateSource(
 *   id = "landing_node_source",
 *   source_module = "wa_migration",
 * )
 */
class LandingNodeSource extends UkNodeSource {

  /**
   * Helper to get the query separately so we can minimise code duplication.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The select query.
   */
  public function getQuery(): SelectInterface {
    return $this->select('node_field_data', 'n')
      ->fields('n')
      ->condition('n.type', $this->configuration['bundle'])
      ->condition('n.nid', [
        656,
        7861,
        14671,
        16391,
        16431,
        14601,
        17911,
        14606,
        14591,
        15176,
        16396,
        17746,
        4671,
        16141,
        16146,
        15616,
        16976,
      ], 'IN');
  }
}
