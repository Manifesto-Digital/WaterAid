<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines the 'wa_orange_dam_video' field type.
 *
 * @FieldType(
 *   id = "wa_orange_dam_video",
 *   label = @Translation("Orange DAM Video"),
 *   description = @Translation("Videos from the Orange DAM"),
 *   default_widget = "wa_orange_dam",
 *   default_formatter = "wa_orange_dam_video_formater",
 * )
 */
final class DamVideoItem extends DamItemBase {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    return [
      'Orange Dam System Identifier' => 'WI11NOYK',
      'width' => 1200,
      'height' => 675,
    ];
  }

}
