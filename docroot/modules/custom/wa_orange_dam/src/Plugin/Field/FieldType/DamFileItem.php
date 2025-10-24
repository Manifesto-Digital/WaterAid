<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'wa_orange_dam_file' field type.
 *
 * @FieldType(
 *   id = "wa_orange_dam_file",
 *   label = @Translation("Orange DAM File"),
 *   description = @Translation("Files from the Orange DAM"),
 *   default_widget = "wa_orange_dam",
 *   default_formatter = "wa_orange_dam_file_formater",
 * )
 */
class DamFileItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    return match ($this->get('system_identifier')->getValue()) {
      NULL, '' => TRUE,
      default => FALSE,
    };
  }

  /**
   * Allows image formater to be run as if it is an entity reference.
   *
   * @return false
   *   Never has an entity attached, so always false.
   */
  public function hasNewEntity(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['system_identifier'] = DataDefinition::create('string')
      ->setLabel(t('Orange Dam System Identifier'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    $columns = [
      'system_identifier' => [
        'type' => 'varchar',
        'not null' => FALSE,
        'description' => 'Orange Dam System Identifier.',
        'length' => 255,
      ],
    ];

    return [
      'columns' => $columns,
    ];
  }

}
