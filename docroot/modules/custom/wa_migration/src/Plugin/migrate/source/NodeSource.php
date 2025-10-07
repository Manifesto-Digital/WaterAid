<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * The 'node_source' source plugin.
 *
 * @MigrateSource(
 *   id = "node_source",
 *   source_module = "wa_migration",
 * )
 */
final class NodeSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    $query = $this->select('node_field_data', 'n')
      ->fields('n')
      ->condition('n.type', $this->configuration['bundle']);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    $return = [
      'title' => $this->t('The Node Name'),
      'langcode' => $this->t('The language'),
      'type' => $this->t('The bundle'),
      'status' => $this->t('The published status'),
      'uid' => $this->t('The creating user'),
    ];

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
    $ids['nid'] = [
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

        if ($data = $this->select('node__' . $field, 'f')
          ->fields('f')
          ->condition('entity_id', $row->getSourceProperty('nid'))
          ->execute()->fetchAll()) {
          foreach ($data as $values) {
            if (isset($values[$field . '_value'])) {
              $value = [$values[$field . '_value']];
            }
            elseif (isset($values[$field . '_target_id'])) {
              $value = [$values[$field . '_target_id']];
            }
          }
        }

        $row->setSourceProperty($field, $value);
      }
    }

    if ($data = $this->select('node__body', 'b')
      ->fields('b')
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->execute()->fetchAssoc()) {
      $row->setSourceProperty('body__value', $data['body_value']);
      $row->setSourceProperty('body__summary', $data['body_summary']);
    }

    return parent::prepareRow($row);
  }

}
