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
class ParagraphSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    $query = $this->select('paragraphs_item_field_data', 'p')
      ->fields('p')
      ->condition('p.type', $this->configuration['bundle']);

    // If this is one of the curated listing migrations, add the required join.
    if (str_starts_with($this->migration->id(), 'curated_listing')) {
      $query->leftJoin('paragraph__field_cl_override', 'o', 'o.entity_id = p.id');

      // For overridden listing we want the override value to be TRUE.
      if (str_starts_with($this->migration->id(), 'curated_listing_overriden')) {
        $query->condition('o.field_cl_override_value', 1);
      }
      else {

        // For all others we either want it to be not set or 0.
        $or = $query->orConditionGroup();
        $or->condition('o.field_cl_override_value', 0);
        $or->isNull('o.field_cl_override_value');

        $query->condition($or);
      }
    }

    return $query;
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
        $value = [];

        if ($data = $this->select('paragraph__' . $field, 'f')
          ->fields('f')
          ->condition('entity_id', $row->getSourceProperty('id'))
          ->execute()->fetchAll()) {
          foreach ($data as $datum) {
            if (isset($datum[$field . '_value'])) {
              if ($field == 'field_vcm_intro') {
                $value[] = substr($datum[$field . '_value'], 0, 252);
              }
              elseif ($field == 'field_cl_display') {
                $value = (int) $datum[$field . '_value'];
              }
              else {
                $value[] = $datum[$field . '_value'];
              }
            }
            elseif (isset($datum[$field . '_target_id'])) {
              if ($field == 'field_activation_bar_item') {
                if ($links = $this->select('paragraph__field_call_to_action_link', 'p')
                  ->fields('p')
                  ->condition('entity_id', $datum[$field . '_target_id'])
                  ->execute()->fetchAll()) {
                  foreach ($links as $link) {
                    $value[] = [
                      $link['field_call_to_action_link_uri'],
                      $link['field_call_to_action_link_title'],
                    ];
                  }
                }
              }
              else {
                $value[] = $datum[$field . '_target_id'];
              }
            }
            elseif (isset($datum[$field . '_uri'])) {
              $value[] = [
                $datum[$field . '_uri'],
                $datum[$field . '_title'],
              ];
            }
          }
        }

        if ($field == 'field_activation_bar_item') {
          $field = 'field_call_to_action_link';
        }
        elseif ($field == 'field_cl_display') {
          $row_value = 1;
          $column_value = '1_column';

          if ($value) {
            $value = is_array($value) ? reset($value) : $value;

            if ($value > 4 && $value <= 8) {
              $row_value = 2;
              $column_value = '2_column';
            }
            elseif ($value > 8 && $value <= 12) {
              $row_value = 3;
              $column_value = '3_column';
            }
            elseif ($value > 12) {
              $row_value = 3;
              $column_value = '4_column';
            }
          }

          $row->setSourceProperty('field_rows', $row_value);
          $row->setSourceProperty('field_card_columns', $column_value);
        }

        $row->setSourceProperty($field, $value);
      }
    }

    return parent::prepareRow($row);
  }

}
