<?php

namespace Drupal\wa_orange_dam\Plugin\media\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\Attribute\MediaSource;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media\Plugin\media\Source\File;
use Drupal\wa_orange_dam\Service\Api;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * DAM Image entity media source.
 */
#[MediaSource(
  id: "dam_image",
  label: new TranslatableMarkup("DAM Image"),
  description: new TranslatableMarkup("Use Orange DAM images for reusable media."),
  allowed_field_types: ["wa_orange_dam_image"],
  default_thumbnail_filename: "no-thumbnail.png",
  thumbnail_alt_metadata_attribute: "thumbnail_alt_value"
)]
class DamImage extends File {

  /**
   * Key for "image width" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_WIDTH = 'width';

  /**
   * Key for "image height" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_HEIGHT = 'height';

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\wa_orange_dam\Service\Api $orange_api
   *   The Orange API service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    FieldTypePluginManagerInterface $field_type_manager,
    ConfigFactoryInterface $config_factory,
    private readonly Api $orange_api,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('wa_orange_dam.api'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $attributes = parent::getMetadataAttributes();

    $attributes += [
      static::METADATA_ATTRIBUTE_WIDTH => $this->t('Width'),
      static::METADATA_ATTRIBUTE_HEIGHT => $this->t('Height'),
    ];

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {

    // If the source field is not required, it may be empty.
    if ($media->get($this->configuration['source_field'])->isEmpty()) {
      return parent::getMetadata($media, $attribute_name);
    }

    $values = $media->get($this->configuration['source_field'])->getValue();

    switch ($attribute_name) {
      case static::METADATA_ATTRIBUTE_WIDTH:
        return $values['width'] ?: NULL;

      case static::METADATA_ATTRIBUTE_HEIGHT:
        return $values['height'] ?: NULL;

      case 'thumbnail_uri':
        $uri = NULL;

        if ($api_result = $this->orange_api->getPublicLink($values['system_identifier'], 'TR1', 100, 100)) {
          if (isset($api_result['link'])) {
            $uri = $api_result['link'];
          }
        }

        return $uri;

      case 'thumbnail_alt_value':
        $alt = parent::getMetadata($media, $attribute_name);

        if ($api_result = $this->orange_api->search([
          'query' => 'SystemIdentifier:' . $values['system_identifier'],
        ])) {
          if (isset($api_result['APIResponse']['Items'][0]['CaptionShort'])) {
            $alt = $api_result['link'];
          }
        }

        return $alt;
    }

    return parent::getMetadata($media, $attribute_name);
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = parent::createSourceField($type);

    // Reset the field to its default settings so that we don't inherit the
    // settings from the parent class' source field.
    $settings = $this->fieldTypeManager->getDefaultFieldSettings($field->getType());

    return $field->set('settings', $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    parent::prepareViewDisplay($type, $display);

    // Use the `large` image style and do not link the image to anything.
    // This will prevent the out-of-the-box configuration from outputting very
    // large raw images. If the `large` image style has been deleted, do not
    // set an image style.
    $field_name = $this->getSourceFieldDefinition($type)->getName();
    $component = $display->getComponent($field_name);
    $component['settings']['image_link'] = '';
    $component['settings']['image_style'] = '';
    if ($this->entityTypeManager->getStorage('image_style')->load('large')) {
      $component['settings']['image_style'] = 'large';
    }
    $display->setComponent($field_name, $component);
  }

}
