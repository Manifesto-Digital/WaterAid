<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateStubInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a paragraph_migration_lookup plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: paragraph_migration_lookup
 *     source: foo
 *     migrations:
 *       - migration1
 *       - migration2
 * @endcode
 *
 * @MigrateProcessPlugin(id = "paragraph_migration_lookup")
 */
final class ParagraphMigrationLookup extends MigrationLookup implements ContainerFactoryPluginInterface {

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
    if (!$value) {
      return NULL;
    }

    $return = NULL;
    $storage = $this->entityTypeManager->getStorage('paragraph');

    if ($new_value = parent::transform($value, $migrate_executable, $row, $destination_property)) {
      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      if ($paragraph = $storage->load($new_value)) {
        if ($paragraph->bundle() == 'temp_paragraph') {
          $return = $this->handleTempParagraph($paragraph);
        }
        else {
          $return = $paragraph;
        }
      }
    }

    if (!$return) {
      return NULL;
    }
    else {
      return $return;
    }
  }

  /**
   * Helper to create/load sub-paragraphs from the temp_paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   A temp_paragraph Paragraph.
   *
   * @return array
   *   An array of paragraphs, in order.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function handleTempParagraph(ParagraphInterface $paragraph): array {
    $return = [];

    $storage = $this->entityTypeManager->getStorage('paragraph');

    $title = $paragraph->get('field_vcm_title')->getString();
    $intro = $paragraph->get('field_intro')->getString();

    if ($title || $intro) {
      $text = ($title) ? '<h3>' . $title . '</h3>' : '';
      $text .= ($intro) ? '<p>' . $intro . '</p>' : '';

      /** @var \Drupal\paragraphs\ParagraphInterface $entity */
      $entity = $storage->create([
        'type' => 'rich_text',
        'field_rich_text' => [
          'value' => $text,
          'format' => 'full_html',
        ],
      ]);
      $entity->enforceIsNew();
      $return[] = $entity;
    }

    if ($links = $paragraph->get('field_links')->getValue()) {
      foreach ($links as $link) {

        /** @var \Drupal\paragraphs\ParagraphInterface $entity */
        $entity = $storage->create([
          'type' => 'call_to_action',
          'field_primary_cta' => $link,
          'field_variant' => 'text_only',
        ]);
        $entity->enforceIsNew();
        $return[] = $entity;
      }
    }

    foreach (['field_main', 'field_aside_section'] as $field) {
      /** @var \Drupal\paragraphs\ParagraphInterface $entity */
      foreach ($paragraph->get($field)->referencedEntities() as $entity) {
        $return[] = $entity;
      }
    }

    return $return;
  }

}
