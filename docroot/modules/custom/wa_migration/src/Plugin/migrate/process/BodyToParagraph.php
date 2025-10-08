<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a body_to_paragraph plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: body_to_paragraph
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(id = "body_to_paragraph")
 */
final class BodyToParagraph extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): mixed {
    if ($value) {
      $paragraph = $this->entityTypeManager->getStorage('paragraph')->create([
        'type' => 'rich_text',
      ]);
      $paragraph->set('field_rich_text', [
        'value' => $value,
        'format' => 'full_html',
      ]);
      $paragraph->enforceIsNew();

      $value = $paragraph;
    }

    return $value;
  }

}
