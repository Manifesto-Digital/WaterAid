<?php

namespace Drupal\wa_orange_dam\Plugin\media\Source;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\Attribute\MediaSource;

/**
 * DAM Image entity media source.
 */
#[MediaSource(
  id: "dam_file",
  label: new TranslatableMarkup("DAM File"),
  description: new TranslatableMarkup("Use Orange DAM files for reusable media."),
  allowed_field_types: ["wa_orange_dam_file"],
  forms: [
    "media_library_add" => "Drupal\wa_orange_dam\Form\AjaxFileForm",
  ],
  default_thumbnail_filename: "no-thumbnail.png",
  thumbnail_alt_metadata_attribute: "thumbnail_alt_value"
)]
final class DamFile extends DamBase {
  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes(): array {
    $attributes = parent::getMetadataAttributes();

    foreach (['width', 'height'] as $property) {
      if (array_key_exists($property, $attributes)) {
        unset($attributes[$property]);
      }
    }

    return $attributes;
  }

}
