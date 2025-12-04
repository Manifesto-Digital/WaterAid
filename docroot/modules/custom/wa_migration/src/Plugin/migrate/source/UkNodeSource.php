<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * The 'uk_node_source' source plugin.
 *
 * @MigrateSource(
 *   id = "uk_node_source",
 *   source_module = "wa_migration",
 * )
 */
class UkNodeSource extends SqlBase {

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
      ->condition('n.status', 1)
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
        13486,
        15616,
        16976,
        16086,
      ], 'NOT IN');
  }

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {

    // We want a single node to not be migrated to the same content type as
    // other nodes of its type. We'll exclude it here and create a custom
    // migration for it.
    $query = $this->getQuery();

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

        if ($field == 'field_article_hero_video') {
          if ($data = $this->select('node__field_article_hero_video', 'f')
            ->fields('f')
            ->condition('entity_id', $row->getSourceProperty('nid'))
            ->execute()->fetchAssoc()
          ) {
            if ($data['field_article_hero_video_target_id']) {
              if ($media = $this->select('media__field_media_video_embed_field', 'm')
                ->fields('m')
                ->condition('entity_id', $data['field_article_hero_video_target_id'])
                ->execute()->fetchAssoc()
              ) {
                if ($media['field_media_video_embed_field_value']) {
                  $row->setSourceProperty('field_media_video_embed_field', $media['field_media_video_embed_field_value']);
                }
              }
            }
          }
        }
        elseif ($field == 'field_resources') {
          if ($data = $this->select('node__' . $field, 'f')
            ->fields('f')
            ->condition('entity_id', $row->getSourceProperty('id'))
            ->execute()->fetchAll()) {
            foreach ($data as $datum) {
              if (isset($datum[$field . '_uri'])) {
                $value[] = [
                  $datum[$field . '_uri'],
                  $datum[$field . '_title'],
                ];
              }
            }
          }
        }
        elseif ($field == 'field_wa_properties') {
          if ($data = $this->select('node__' . $field, 'f')
            ->fields('f')
            ->condition('entity_id', $row->getSourceProperty('nid'))
            ->execute()->fetchAll()) {
            foreach ($data as $datum) {
              if (isset($datum[$field . '_target_id'])) {
                $query = $this->select('paragraphs_item_field_data', 'p')
                  ->fields('p')
                  ->condition('p.id', $datum[$field . '_target_id']);
                $query->leftJoin('paragraph__field_property_label', 'l', 'l.entity_id = p.id');
                $query->fields('l', ['field_property_label_value']);
                $query->leftJoin('paragraph__field_property_value', 'v', 'v.entity_id = p.id');
                $query->fields('v', ['field_property_value_value']);

                if ($results = $query->execute()->fetchAll()) {
                  foreach ($results as $result) {
                    $value[] = [
                      'key' => $result['field_property_label_value'],
                      'value' => $result['field_property_value_value'],
                    ];
                  }
                }
              }
            }
          }
        }
        else {
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
                $body_fields = [
                  'field_wa_page_sections',
                  'field_listing',
                  'field_modules',
                ];
                if (in_array($field, $body_fields)) {
                  $value_found = FALSE;

                  foreach ([
                    'field_column_1',
                    'field_column_2',
                    'field_section_item',
                    'field_enhanced_carousel_items',
                    'field_tab_items',
                    'field_quotes_quote',
                    'field_biography_item',
                    'field_rainbow_links',
                    'field_donation_cta_widget',
                  ] as $table) {
                    if ($sub_paragraphs = $this->select('paragraph__' . $table, 'p')
                      ->fields('p')
                      ->condition('entity_id', $values[$field . '_target_id'])
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
          $start[] = $this->dateFormat($values['field_event_date_value']);
        }
        if (isset($values['field_event_date_end_value'])) {
          $end[] = $this->dateFormat($values['field_event_date_end_value']);
        }
      }

      $row->setSourceProperty('field_event_date_start', $start);
      $row->setSourceProperty('field_event_date_end', $end);
    }

    return parent::prepareRow($row);
  }

  /**
   * Helper to update a date to the right format.
   *
   * @param string $date
   *   A date string.
   *
   * @return string
   *   the updated date.
   */
  private function dateFormat(string $date): string {
    if (strlen($date) == 10) {
      $new_date = DrupalDateTime::createFromFormat('Y-m-d', $date);
      $new_date->setTime(0, 0);
      $date = $new_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    }

    return $date;
  }

}
