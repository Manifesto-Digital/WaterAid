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
 * Provides an image_or_video_to_hero plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: image_or_video_to_hero
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(id = "image_or_video_to_hero")
 */
final class ImageOrVideoToHero extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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
    $type = NULL;
    $type = (!empty($value[0][0])) ? 'hero_image' : $type;
    $type = (!empty($value[1][0])) ? 'hero_video' : $type;

    if ($type) {

      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = $this->entityTypeManager->getStorage('paragraph')->create([
        'type' => $type,
      ]);

      if ($type == 'hero_image') {
        if ($media = $this->entityTypeManager->getStorage('media')->load($value[0][0])) {
          $paragraph->set('field_image', $media);
        }
      }
      else {
        $media = $this->entityTypeManager->getStorage('media')->create([
          'bundle' => 'remote_video',
          'uid' => 1,
        ]);
        $media->set('field_media_oembed_video', $value[1][0]);

        $paragraph->set('field_video', $media);
      }

      $paragraph->enforceIsNew();

      return $paragraph;
    }

    return NULL;
  }

}
