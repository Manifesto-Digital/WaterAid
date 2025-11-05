<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Row;

/**
 * The 'listing_paragraph_source' source plugin.
 *
 * @MigrateSource(
 *   id = "listing_paragraph_source",
 *   source_module = "wa_migration",
 * )
 */
final class ListingParagraphSource extends ParagraphSource {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    return $this->select('paragraphs_item_field_data', 'p')
      ->fields('p')
      ->condition('p.type', 'listing');
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    $return = parent::fields();

    $return['field_listing_item'] = $this->t('The links to the entity to reference');

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
    $value = [];

    if ($ids = $this->select('paragraph__field_listing_item', 'f')
      ->fields('f', ['field_listing_item_target_id'])
      ->condition('entity_id', $row->getSourceProperty('id'))
      ->execute()->fetchAll()) {
      $pids = [];

      foreach ($ids as $id) {
        if (isset($id['field_listing_item_target_id'])) {
          $pids[] = $id['field_listing_item_target_id'];
        }
      }

      foreach ($this->select('paragraph__field_call_to_action_link', 'c')
        ->fields('c')
        ->condition('entity_id', $pids, 'IN')
        ->execute()->fetchAll() as $datum) {
        if (isset($datum['field_call_to_action_link_uri'])) {
          if (str_starts_with($datum['field_call_to_action_link_uri'], 'entity:node/')) {
            $value[] = substr($datum['field_call_to_action_link_uri'], 12);
          }
        }
      }
    }

    if (!empty($value)) {
      $row->setSourceProperty('field_listing_item', $value);
    }

    return parent::prepareRow($row);
  }

}
