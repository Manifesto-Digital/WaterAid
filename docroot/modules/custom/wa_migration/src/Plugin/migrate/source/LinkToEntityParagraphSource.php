<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * The 'link_to_entity_paragraph_source' source plugin.
 *
 * @MigrateSource(
 *   id = "link_to_entity_paragraph_source",
 *   source_module = "wa_migration",
 * )
 */
class LinkToEntityParagraphSource extends ParagraphSource {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row): bool {
    $value = [];

    foreach ($this->select('paragraph__field_call_to_action_link', 'c')
      ->fields('c')
      ->condition('entity_id', $row->getSourceProperty('id'))
      ->execute()->fetchAll() as $datum) {
      if (isset($datum['field_call_to_action_link_uri'])) {
        if (str_starts_with($datum['field_call_to_action_link_uri'], 'entity:node/')) {
          $value[] = substr($datum['field_call_to_action_link_uri'], 12);
        }
      }
    }

    if (!empty($value)) {
      $row->setSourceProperty('field_call_to_action_link', $value);
    }

    return parent::prepareRow($row);
  }

}
