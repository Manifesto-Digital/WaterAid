<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'wa_orange_dam_image' field type.
 *
 * @FieldType(
 *   id = "wa_orange_dam_image",
 *   label = @Translation("Orange DAM Image"),
 *   description = @Translation("Some description."),
 *   default_widget = "string_textfield",
 *   default_formatter = "string",
 * )
 */
final class DamImageItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    return match ($this->get('value')->getValue()) {
      NULL, '' => TRUE,
      default => FALSE,
    };
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

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    return [
      'Orange Dam System Identifier' => 'WI11NOKK',
    ];
  }

}
