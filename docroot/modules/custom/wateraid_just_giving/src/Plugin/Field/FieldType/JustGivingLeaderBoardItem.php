<?php

namespace Drupal\wateraid_just_giving\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'webform_entity_reference' entity field type.
 *
 * Extends EntityReferenceItem and only support targeting webform entities.
 *
 * @FieldType(
 *   id = "just_giving_leaderboard",
 *   label = @Translation("JustGiving Leaderboard"),
 *   description = @Translation("A JustGiving Leader Board."),
 *   category = @Translation("JustGiving"),
 *   default_widget = "just_giving_leaderboard_default_widget",
 *   default_formatter = "just_giving_leaderboard"
 * )
 */
class JustGivingLeaderBoardItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['type'] = DataDefinition::create('string')
      ->setLabel(t('Type'))
      ->setRequired(TRUE);

    $properties['just_giving_id'] = DataDefinition::create('integer')
      ->setLabel(t('JustGiving Id'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'type' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'just_giving_id' => [
          'type' => 'int',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $item = $this->getValue();
    return empty($item['type']) && empty($item['just_giving_id']);
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName(): ?string {
    return 'just_giving_id';
  }

}
