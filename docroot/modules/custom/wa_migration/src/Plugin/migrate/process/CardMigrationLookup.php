<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\process;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateStubInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a card_migration_lookup plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: card_migration_lookup
 *     source: foo
 *     migration:
 *       - migration1
 *       - migration2
 * @endcode
 *
 * @MigrateProcessPlugin(id = "card_migration_lookup")
 */
final class CardMigrationLookup extends MigrationLookup implements ContainerFactoryPluginInterface {

  /**
   * Constructs a MigrationLookup object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The Migration the plugin is being used in.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   * @param \Drupal\migrate\MigrateStubInterface $migrate_stub
   *   The migrate stub service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    MigrateLookupInterface $migrate_lookup,
    MigrateStubInterface $migrate_stub,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $migrate_lookup, $migrate_stub);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('migrate.lookup'),
      $container->get('migrate.stub'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): mixed {
    $paragraph = [
      'type' => 'external_card',
    ];

    if (!is_array($value)) {
      $paragraph['type'] = 'internal_card';
      if ($new_nid = parent::transform($value, $migrate_executable, $row, $destination_property)) {
        $paragraph['field_internal_card'] = ['target_id' => $new_nid];
      }
      else {

        // Add a broken link.
        $paragraph['field_internal_card'] = ['target_id' => $value];
      }
    }
    else {
      if (isset($value[0])) {
        if (str_starts_with($value[0], 'entity:node/') || str_starts_with($value[0], 'internal:node/')) {
          $nid = (str_starts_with($value[0], 'entity:node/')) ? substr($value[0], 12) : substr($value[0], 15);

          if ($new_nid = parent::transform($nid, $migrate_executable, $row, $destination_property)) {
            $paragraph['type'] = 'internal_card';
            $paragraph['field_internal_card'] = ['target_id' => $new_nid];
          }
          else {
            $paragraph['field_link'] = [
              'uri' => $value[0],
              'title' => $value[1],
            ];
          }
        }
        else {
          $paragraph['field_link'] = [
            'uri' => $value[0],
            'title' => $value[1],
          ];
        }
      }

      if ($paragraph['type'] == 'external_card') {
        if (isset($value[2]) || isset($value[3])) {
          $text = (isset($value[3])) ? $value[3] : '';
          $text .= (isset($value[2])) ? $value[2] : '';

          if ($text !== '') {
            $paragraph['field_description'] = $text;
          }
        }
        if (isset($value[4])) {

          // Store the old config so we can put it back.
          $old_config = $this->configuration['migration'];
          $this->configuration['migration'] = 'media';
          $this->configuration['no_stub'] = TRUE;

          if ($mid = parent::transform($value[4], $migrate_executable, $row, $destination_property)) {
            $paragraph['field_media'] = [
              'target_id' => $mid,
            ];
          }

          // Put the old config back so we don't break the next link look-up.
          $this->configuration['migration'] = $old_config;
        }
      }
    }

    $entity = $this->entityTypeManager->getStorage('paragraph')->create($paragraph);
    $entity->enforceIsNew();

    return $entity;
  }

}
