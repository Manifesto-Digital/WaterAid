<?php

namespace Drupal\wa_orange_dam\Plugin\media\Source;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\Attribute\MediaSource;

/**
 * DAM Image entity media source.
 */
#[MediaSource(
  id: "dam_video",
  label: new TranslatableMarkup("DAM Video"),
  description: new TranslatableMarkup("Use Orange DAM video for reusable media."),
  allowed_field_types: ["wa_orange_dam_video"],
  forms: [
    "media_library_add" => "Drupal\wa_orange_dam\Form\AjaxVideoForm",
  ],
  default_thumbnail_filename: "no-thumbnail.png",
  thumbnail_alt_metadata_attribute: "thumbnail_alt_value"
)]
final class DamVideo extends DamBase {}
