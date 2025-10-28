<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines the 'wa_orange_dam_image' field type.
 *
 * @FieldType(
 *   id = "wa_orange_dam_image",
 *   label = @Translation("Orange DAM Image"),
 *   description = @Translation("Images from the Orange DAM"),
 *   default_widget = "wa_orange_dam",
 *   default_formatter = "wa_orange_dam_image_formater",
 * )
 */
final class DamImageItem extends DamItemBase {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    return [
      'Orange Dam System Identifier' => 'WI11NONT',
      'width' => 1200,
      'height' => 1067,
    ];
  }

}
