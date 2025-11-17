<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;

/**
 * The 'involved_node_source' source plugin.
 *
 * @MigrateSource(
 *   id = "involved_node_source",
 *   source_module = "wa_migration",
 * )
 */
class InvolvedNodeSource extends NodeSource {

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
        13486,
      ], 'IN');
  }
}
