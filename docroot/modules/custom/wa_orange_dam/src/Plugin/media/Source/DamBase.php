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
class DamBase extends File {

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
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Psr\Log\LoggerInterface $logger
   * *   The logger channel for the module.
 */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    FieldTypePluginManagerInterface $field_type_manager,
    ConfigFactoryInterface $config_factory,
    readonly Api $orange_api,
    readonly FileSystemInterface $file_system,
    readonly LoggerInterface $logger,
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
      $container->get('file_system'),
      $container->get('logger.factory')->get('wa_orange_dam'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes(): array {
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
        return $values[0]['width'] ?: NULL;

      case static::METADATA_ATTRIBUTE_HEIGHT:
        return $values[0]['height'] ?: NULL;

      case 'thumbnail_uri':
        return $this->getLocalThumbnailUri($values[0]['system_identifier']);

      case 'thumbnail_alt_value':
        $alt = parent::getMetadata($media, $attribute_name);

        if ($api_result = $this->orange_api->search([
          'query' => 'SystemIdentifier:' . $values[0]['system_identifier'],
        ])) {
          if (isset($api_result['APIResponse']['Items'][0]['CustomField.Caption'])) {
            $alt = $api_result['APIResponse']['Items'][0]['CustomField.Caption'];

            // Ensure long captions aren't too long for the database.
            $alt = substr($alt, 0, 250);
          }
        }

        return $alt;
    }

    return parent::getMetadata($media, $attribute_name);
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type): FieldConfigInterface|EntityInterface {
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = parent::createSourceField($type);

    // Reset the field to its default settings so that we don't inherit the
    // settings from the parent class' source field.
    $settings = $this->fieldTypeManager->getDefaultFieldSettings($field->getType());

    return $field->set('settings', $settings);
  }

  /**
   * Returns the local URI for a resource thumbnail.
   *
   * If the thumbnail is not already locally stored, this method will attempt
   * to download it.
   *
   * @param string $system_identifier
   *   The URl of the thumbnail.
   *
   * @return string|null
   *   The local thumbnail URI, or NULL if it could not be downloaded, or if the
   *   resource has no thumbnail at all.
   */
  protected function getLocalThumbnailUri(string $system_identifier): ?string {
    return NULL;
  }

}
