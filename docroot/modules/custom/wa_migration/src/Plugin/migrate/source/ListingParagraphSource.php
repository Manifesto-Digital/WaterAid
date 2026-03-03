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
    $query = $this->select('paragraphs_item_field_data', 'p')
      ->fields('p')
      ->condition('p.type', 'listing');

    $settings = $this->migration->getPluginDefinition();
    $migration_group = $settings['migration_group'] ?? NULL;

    // If this is Washmatters, only bring in English content.
    if (str_starts_with($migration_group, 'wateraid_wash')) {
      $query->condition('p.langcode', 'en');
    }

    return $query;
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

    $settings = $this->migration->getPluginDefinition();
    $migration_group = $settings['migration_group'] ?? NULL;
    $washmatters = str_starts_with($migration_group, 'wateraid_wash');

    $query = $this->select('paragraph__field_listing_item', 'f')
      ->fields('f', ['field_listing_item_target_id'])
      ->condition('entity_id', $row->getSourceProperty('id'));

    if ($washmatters) {
      $query->condition('f.langcode', 'en');
    }

    if ($ids = $query->execute()->fetchAll()) {
      $pids = [];

      foreach ($ids as $id) {
        if (isset($id['field_listing_item_target_id'])) {
          $pids[] = $id['field_listing_item_target_id'];
        }
      }

      $query = $this->select('paragraphs_item_field_data', 'p')
        ->fields('p')
        ->condition('p.id', $pids, 'IN');

      if ($washmatters) {
        $query->condition('p.langcode', 'en');
      }

      $condition = ($washmatters) ? 'p.id = c.entity_id AND c.langcode = :langcode' : 'p.id = c.entity_id';
      $query->leftJoin('paragraph__field_call_to_action_link', 'c', $condition, [':langcode' => 'en']);
      $query->fields('c');

      $condition = ($washmatters) ? 'd.entity_id = p.id AND d.langcode = :langcode' : 'd.entity_id = p.id';
      $query->leftJoin('paragraph__field_listing_item_details', 'd', $condition, [':langcode' => 'en']);
      $query->fields('d');

      $condition = ($washmatters) ? 't.entity_id = p.id AND t.langcode = :langcode' : 't.entity_id = p.id';
      $query->leftJoin('paragraph__field_listing_item_title', 't', $condition, [':langcode' => 'en']);
      $query->fields('t');

      $condition = ($washmatters) ? 'i.entity_id = p.id AND i.langcode = :langcode' : 'i.entity_id = p.id';
      $query->leftJoin('paragraph__field_image', 'i', $condition, [':langcode' => 'en']);
      $query->fields('i');

      foreach ($query->execute()->fetchAll() as $datum) {
        $value[] = [
          $datum['field_call_to_action_link_uri'],
          $datum['field_call_to_action_link_title'],
          isset($datum['field_listing_item_details_value']) ? strip_tags($datum['field_listing_item_details_value']) : '',
          isset($datum['field_listing_item_title_value']) ? strip_tags($datum['field_listing_item_title_value']) : '',
          $datum['field_image_target_id'],
          $datum['id'],
        ];
      }
    }

    if (!empty($value)) {
      $row->setSourceProperty('field_listing_item', $value);
    }

    return parent::prepareRow($row);
  }

}
