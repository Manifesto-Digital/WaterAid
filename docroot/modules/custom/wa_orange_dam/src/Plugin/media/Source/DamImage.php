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
    private readonly Api $orange_api,
    private readonly FileSystemInterface $file_system,
    private readonly LoggerInterface $logger,
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
          if (isset($api_result['APIResponse']['Items'][0]['CaptionShort'])) {
            $alt = $api_result['APIResponse']['Items'][0]['CaptionShort'];
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
    $remote_thumbnail_url = NULL;

    // If there is no remote thumbnail, there's nothing for us to fetch here.
    if ($api_result = $this->orange_api->getPublicLink($system_identifier, NULL, 100, 100)) {
      if (isset($api_result['link'])) {
        $remote_thumbnail_url = $api_result['link'];
      }
    }

    if (!$remote_thumbnail_url) {
      return NULL;
    }

    $directory = 'public://orange_dam_thumbnails';

    // The local thumbnail doesn't exist yet, so try to download it. First,
    // ensure that the destination directory is writable, and if it's not,
    // log an error and bail out.
    if (!$this->file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      $this->logger->warning('Could not prepare thumbnail destination directory @dir for Orange DAM media.', [
        '@dir' => $directory,
      ]);
      return NULL;
    }

    // The local filename of the thumbnail is always a hash of its remote URL.
    // If a file with that name already exists in the thumbnails directory,
    // regardless of its extension, return its URI.
    $hash = Crypt::hashBase64($remote_thumbnail_url);
    $files = $this->file_system->scanDirectory($directory, "/^$hash\..*/");
    if (count($files) > 0) {
      return reset($files)->uri;
    }

    // The local thumbnail doesn't exist yet, so we need to create it.
    try {
      $path = parse_url($remote_thumbnail_url, PHP_URL_PATH);
      $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

      $contents = file_get_contents($remote_thumbnail_url);

      $local_thumbnail_uri = $directory . DIRECTORY_SEPARATOR . $hash . '.' . $extension;
      $this->file_system->saveData($contents, $local_thumbnail_uri, FileExists::Replace);

      return $local_thumbnail_uri;
    }
    catch (FileException $e) {
      $this->logger->warning('Could not download remote thumbnail from {url}.', [
        'url' => $remote_thumbnail_url,
      ]);
    }

    return NULL;
  }

}
