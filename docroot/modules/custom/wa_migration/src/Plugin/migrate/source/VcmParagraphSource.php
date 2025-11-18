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
        $value = [];

        if ($data = $this->select('paragraph__' . $field, 'f')
          ->fields('f')
          ->condition('entity_id', $row->getSourceProperty('id'))
          ->execute()->fetchAll()) {
          foreach ($data as $datum) {
            if (isset($datum[$field . '_value'])) {
              $value[] = $datum[$field . '_value'];
            }
            elseif (isset($datum[$field . '_target_id'])) {
              $value[] = $datum[$field . '_target_id'];
            }
            elseif (isset($datum[$field . '_uri'])) {
              $value[] = [
                $datum[$field . '_uri'],
                $datum[$field . '_title'],
              ];
            }
          }
        }

        $row->setSourceProperty($field, $value);
      }
    }

    return TRUE;
  }

}
