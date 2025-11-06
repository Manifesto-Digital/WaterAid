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

    $field = $this->configuration['bundle'] == 'rainbow_link_item' ? 'field_call_to_action_link_single' : 'field_call_to_action_link';

    foreach ($this->select('paragraph__' . $field, 'c')
      ->fields('c')
      ->condition('entity_id', $row->getSourceProperty('id'))
      ->execute()->fetchAll() as $datum) {
      if (isset($datum[$field . '_uri'])) {
        if (str_starts_with($datum[$field . '_uri'], 'entity:node/')) {
          $value[] = substr($datum[$field . '_uri'], 12);
        }
      }
    }

    if (!empty($value)) {
      $row->setSourceProperty('field_call_to_action_link', $value);
    }

    return parent::prepareRow($row);
  }

}
