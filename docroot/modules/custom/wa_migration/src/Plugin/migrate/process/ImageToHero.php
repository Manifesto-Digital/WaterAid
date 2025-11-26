<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\process;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an image_to_hero plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: image_to_hero
 *     source: foo
 *     hero_type: <type of hero paragraph to create>
 * @endcode
 *
 * @MigrateProcessPlugin(id = "image_to_hero")
 */
final class ImageToHero extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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
    if (!empty($value[0]) || !empty($value[1]) || !empty($value[2]) || !empty($value[3])) {

      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = $this->entityTypeManager->getStorage('paragraph')->create([
        'type' => $this->configuration['hero_type'],
      ]);

      if (isset($value[0][0])) {
        if ($media = $this->entityTypeManager->getStorage('media')
          ->load($value[0][0])) {
          if ($paragraph->hasField('field_image')) {
            $paragraph->set('field_image', $media);
          }
        }
      }

      if (!empty($value[1]) && $paragraph->hasField('field_authors')) {
        $paragraph->set('field_authors', $value[1]);
      }

      if (!empty($value[2]) && $paragraph->hasField('field_standfirst')) {
        $paragraph->set('field_standfirst', [
          'value' => $value[2],
          'format' => 'basic_html',
        ]);
      }

      if (!empty($value[3]) && $paragraph->hasField('field_published_date')) {
        $date = DrupalDateTime::createFromTimestamp($value[3]);

        $paragraph->set('field_published_date', $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT));
      }

      $paragraph->enforceIsNew();

      return $paragraph;
    }

    return NULL;
  }

}
