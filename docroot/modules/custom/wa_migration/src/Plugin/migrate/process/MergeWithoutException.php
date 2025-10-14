<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class MergeWithoutException extends ProcessPluginBase implements ContainerFactoryPluginInterface {


  /**
   * Constructs the plugin instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): ?array {
    $return = [];

    if (is_array($value)) {
      foreach ($value as $item) {
        if (!empty($item)) {
          if (is_array($item)) {
            foreach ($item as $sub) {
              if (!empty($sub)) {
                $return[] = $sub;
              }
            }
          }
          else {
            $return[] = $item;
          }
        }
      }
    }
    else {
      $return = $value;
    }

    // If we have an entity id here, try to load it.
    if (!empty($return)) {
      $entity_type = ($destination_property == 'field_content') ? 'paragraph' : 'node';
      $storage = $this->entityTypeManager->getStorage($entity_type);

      foreach ($return as $key => $item) {
        if (is_int($item) || is_string($item)) {

          /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
          if ($entity = $storage->load($item)) {

            // Make sure the paragraph parent update gets saved.
            if ($entity_type == 'paragraph') {
              $entity->setNeedsSave(TRUE);
            }
            $return[$key] = $entity;
          }
        }
      }
    }
    else {
      $return = NULL;
    }

    return $return;
  }

}
