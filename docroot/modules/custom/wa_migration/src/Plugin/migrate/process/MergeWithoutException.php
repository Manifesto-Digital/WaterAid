<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * This plugin merges arrays together.
 *
 * @MigrateProcessPlugin(
 *   id = "merge_without_exception"
 * )
 *
 * Use to merge several fields into one. In the following example, imagine a D7
 * node with a field_collections field and an image field that migrations were
 * written for to make paragraph entities in D8. We would like to add those
 * paragraph entities to the 'paragraphs_field'. Consider the following:
 *
 *  source:
 *    plugin: d7_node
 *  process:
 *    temp_body:
 *      plugin: sub_process
 *      source: field_section
 *      process:
 *        target_id:
 *          plugin: migration_lookup
 *          migration: field_collection_field_section_to_paragraph
 *          source: value
 *    temp_images:
 *      plugin: sub_process
 *      source: field_image
 *      process:
 *        target_id:
 *          plugin: migration_lookup
 *          migration: image_entities_to_paragraph
 *          source: fid
 *    paragraphs_field:
 *      plugin: merge
 *      source:
 *        - '@temp_body'
 *        - '@temp_images'
 *  destination:
 *    plugin: 'entity:node'
 */
class MergeWithoutException extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): ?array {
    $return = [];

    if (is_array($value)) {
      foreach ($value as $item) {
        if ($item) {
          $return[] = $item;
        }
      }
    }
    else {
      $return = $value;
    }

    return (empty($return)) ? NULL : $return;
  }

}
