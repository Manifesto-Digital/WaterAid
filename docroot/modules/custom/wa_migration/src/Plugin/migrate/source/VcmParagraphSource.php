<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * The 'vcm_paragraph_source' source plugin.
 *
 * @MigrateSource(
 *   id = "vcm_paragraph_source",
 *   source_module = "wa_migration",
 * )
 */
class VcmParagraphSource extends ParagraphSource {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row): bool {
    parent::prepareRow($row);

    if (isset($this->configuration['fields'])) {
      foreach ($this->configuration['fields'] as $field) {
        $value = NULL;

        if ($field == 'field_vcm_links') {
          $value = [];
          if ($data = $this->select('paragraph__' . $field, 'f')
            ->fields('f')
            ->condition('entity_id', $row->getSourceProperty('id'))
            ->execute()->fetchAll()) {
            $one = 1;
            foreach ($data as $datum) {
              if (isset($datum[$field . '_uri'])) {
                $value[] = [
                  $datum[$field . '_uri'],
                  $datum[$field . '_title'],
                ];
              }
            }
          }

          if (empty($value)) {
            $value = NULL;
          }
        }
        else {
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
        }

        $row->setSourceProperty($field, $value);
      }
    }

    return TRUE;
  }

}
