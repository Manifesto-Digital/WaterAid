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

      $query = $this->select('paragraphs_item_field_data', 'p')
        ->fields('p')
        ->condition('p.id', $pids, 'IN');

      $query->leftJoin('paragraph__field_call_to_action_link', 'c', 'p.id = c.entity_id');
      $query->fields('c');
      $query->leftJoin('paragraph__field_listing_item_details', 'd', 'd.entity_id = p.id');
      $query->fields('d');
      $query->leftJoin('paragraph__field_listing_item_title', 't', 't.entity_id = p.id');
      $query->fields('t');
      $query->leftJoin('paragraph__field_image', 'i', 'i.entity_id = p.id');
      $query->fields('i');

      foreach ($query->execute()->fetchAll() as $datum) {
        $value[] = [
          $datum['field_call_to_action_link_uri'],
          $datum['field_call_to_action_link_title'],
          isset($datum['field_listing_item_details_value']) ? strip_tags($datum['field_listing_item_details_value']) : '',
          isset($datum['field_listing_item_title_value']) ? strip_tags($datum['field_listing_item_title_value']) : '',
          $datum['field_image_target_id'],
        ];
      }
    }

    if (!empty($value)) {
      $row->setSourceProperty('field_listing_item', $value);
    }

    return parent::prepareRow($row);
  }

}
