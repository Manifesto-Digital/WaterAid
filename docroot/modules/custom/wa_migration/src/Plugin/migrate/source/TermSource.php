<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * The 'term_source' source plugin.
 *
 * @MigrateSource(
 *   id = "term_source",
 *   source_module = "wa_migration",
 * )
 */
final class TermSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    $query = $this->select('taxonomy_term_field_data', 't')
      ->fields('t')
      ->condition('t.vid', $this->configuration['bundle']);
    $query->leftJoin('taxonomy_term__parent', 'p', 'p.entity_id = t.tid');
    $query->fields('p', ['parent_target_id']);
    $query->orderBy('p.parent_target_id');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    $return = [
      'name' => $this->t('The Term Name'),
      'langcode' => $this->t('The language'),
      'vid' => $this->t('The vocabulary'),
      'status' => $this->t('The published status'),
      'description' => $this->t('The Term Description'),
      'weight' => $this->t('The weight'),
      'parent' => $this->t('The term parent'),
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
    $ids['tid'] = [
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

        if ($data = $this->select('taxonomy_term__' . $field, 'f')
          ->fields('f')
          ->condition('entity_id', $row->getSourceProperty('tid'))
          ->execute()->fetchAssoc()) {
          if (isset($data[$field . '_value'])) {
            $value = $data[$field . '_value'];
          }
          elseif (isset($data[$field . '_uri'])) {
            $value = $data[$field . '_uri'];
          }
        }

        $row->setSourceProperty($field, $value);
      }
    }

    if ($parent = $row->getSourceProperty('parent_target_id')) {
      $row->setSourceProperty('parent', [$parent]);
    }

    return parent::prepareRow($row);
  }

}
