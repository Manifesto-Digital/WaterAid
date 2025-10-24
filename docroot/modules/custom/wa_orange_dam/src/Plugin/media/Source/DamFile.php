<?php

namespace Drupal\wa_orange_dam\Plugin\media\Source;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\FieldConfigInterface;
use Drupal\media\Attribute\MediaSource;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media\Plugin\media\Source\File;
use Drupal\wa_orange_dam\Service\Api;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * DAM Image entity media source.
 */
#[MediaSource(
  id: "dam_file",
  label: new TranslatableMarkup("DAM File"),
  description: new TranslatableMarkup("Use Orange DAM files for reusable media."),
  allowed_field_types: ["wa_orange_dam_file"],
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
