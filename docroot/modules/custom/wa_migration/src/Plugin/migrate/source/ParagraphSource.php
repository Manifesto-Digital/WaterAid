<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * The 'paragraph_source' source plugin.
 *
 * @MigrateSource(
 *   id = "paragraph_source",
 *   source_module = "wa_migration",
 * )
 */
final class ParagraphSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    return $this->select('paragraphs_item_field_data', 'p')
      ->fields('p')
      ->condition('p.type', $this->configuration['bundle']);
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    $return = [];

    if (isset($this->configuration['fields'])) {
      foreach ($this->configuration['fields'] as $field) {
        $return[$field] = $this->t('The :field field', [
          ':field' => $field,
        ]);
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    $ids['id'] = [
      'type' => 'integer',
    ];

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row): bool {
    if (isset($this->configuration['fields'])) {
      foreach ($this->configuration['fields'] as $field) {
        $value = NULL;

        if ($data = $this->select('paragraph__' . $field, 'f')
          ->fields('f')
          ->condition('entity_id', $row->getSourceProperty('id'))
          ->execute()->fetchAssoc()) {
          if (isset($data[$field . '_value'])) {
            $value = $data[$field . '_value'];
          }
          elseif (isset($data[$field . '_target_id'])) {
            $value = $data[$field . '_target_id'];
          }
          elseif ((isset($data[$field . '_uri']))) {
            $row->setSourceProperty($field . '_uri', $data[$field . '_uri']);
            $row->setSourceProperty($field . '_title', $data[$field . '_title']);
          }
        }

        $row->setSourceProperty($field, $value);
      }
    }

    return parent::prepareRow($row);
  }

}
