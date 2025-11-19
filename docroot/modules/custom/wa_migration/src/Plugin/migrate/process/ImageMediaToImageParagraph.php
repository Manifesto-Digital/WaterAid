<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an image_media_to_image_paragraph plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: image_media_to_image_paragraph
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(id = "image_media_to_image_paragraph")
 */
final class ImageMediaToImageParagraph extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs the plugin instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): ?ParagraphInterface {
    if ($value) {
      if ($media = $this->entityTypeManager->getStorage('media')->load($value)) {

        /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
        $paragraph = $this->entityTypeManager->getStorage('paragraph')->create([
          'type' => 'image',
        ]);
        $paragraph->set('field_image', $media);
        $paragraph->enforceIsNew();

        return $paragraph;
      }
    }

    return NULL;
  }

}
