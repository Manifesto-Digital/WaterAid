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

    if (isset($this->configuration['fields'])) {
      if (in_array('field_wa_donation_page_template', $this->configuration['fields'])) {
        $query->leftJoin('node__field_wa_donation_page_template', 't', 't.entity_id = n.nid');
        $query->orderBy('field_wa_donation_page_template_target_id');
      }
      if (in_array('field_related_content', $this->configuration['fields'])) {
        $query->leftJoin('node__field_related_content', 't', 't.entity_id = n.nid');
        $query->orderBy('field_related_content_target_id');
      }
    }

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
        $value = [];

        if ($data = $this->select('node__' . $field, 'f')
          ->fields('f')
          ->condition('entity_id', $row->getSourceProperty('nid'))
          ->execute()->fetchAll()) {
          foreach ($data as $values) {
            if (isset($values[$field . '_value'])) {
              $value[] = $values[$field . '_value'];
            }
            elseif (isset($values[$field . '_target_id'])) {

              // Check if we have values nested inside the structural paragraphs
              // we are no longer using.
              if ($field == 'field_modules') {
                $value_found = FALSE;

                foreach ([
                  'field_column_1',
                  'field_column_2',
                  'field_section_item',
                  'field_vcm_main',
                  'field_enhanced_carousel_items',
                  'field_tab_items',
                  'field_quotes_quote',
                  'field_biography_item',
                  'field_activation_bar_item',
                  'field_rainbow_links',
                ] as $table) {
                  if ($sub_paragraphs = $this->select('paragraph__' . $table, 'p')
                    ->fields('p')
                    ->condition('entity_id', $values['field_modules_target_id'])
                    ->execute()
                    ->fetchAll()
                  ) {
                    $value_found = TRUE;

                    // If we've found sub-paragraphs, add these into the value
                    // instead of the parent id, because it is only the children
                    // we've migrated in for the paragraphs with these fields.
                    foreach ($sub_paragraphs as $sub) {
                      if (isset($sub[$table . '_target_id'])) {
                        $value[] = $sub[$table . '_target_id'];
                      }
                    }
                  }
                }

                // If we haven't found any data linking this target idea to
                // sub-paragraphs, we'll add it into the values so the migration
                // lookup can find any paragraphs it relates to.
                if (!$value_found) {
                  $value[] = $values[$field . '_target_id'];
                }
              }
              else {
                $value[] = $values[$field . '_target_id'];
              }
            }
            elseif ((isset($values[$field . '_uri']))) {
              $row->setSourceProperty($field . '_uri', $values[$field . '_uri']);
              $row->setSourceProperty($field . '_title', $values[$field . '_title']);
            }
          }
        }

        $value = (empty($value)) ? NULL : $value;

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

    if ($data = $this->select('node__field_event_date', 'e')
      ->fields('e')
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->execute()->fetchAll()) {

      $start = $end = [];

      foreach ($data as $values) {
        if (isset($values['field_event_date_value'])) {
          $start[] = $values['field_event_date_value'];
        }
        if (isset($values['field_event_date_end_value'])) {
          $end[] = $values['field_event_date_end_value'];
        }
      }

      $row->setSourceProperty('field_event_date_start', $start);
      $row->setSourceProperty('field_event_date_end', $end);
    }

    return parent::prepareRow($row);
  }

}
