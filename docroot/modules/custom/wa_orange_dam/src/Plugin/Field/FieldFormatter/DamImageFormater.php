<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\wa_orange_dam\Service\Api;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'DAM Image Formater' formatter.
 *
 * @FieldFormatter(
 *   id = "wa_orange_dam_image_formater",
 *   label = @Translation("DAM Image Formater"),
 *   field_types = {"wa_orange_dam_image"},
 * )
 */
final class DamImageFormater extends FormatterBase {

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\wa_orange_dam\Service\Api $orangeDamApi
   *   The Orange DAM API.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    private readonly Api $orangeDamApi,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('wa_orange_dam.api'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];
    foreach ($items as $delta => $item) {
      $item_values = $item->getValue();
      $data = $this->orangeDamApi->getPublicLink($item_values['system_identifier'], 'TR1', $item_values['width'], $item_values['height']);
      $search = $this->orangeDamApi->search(['query' => 'SystemIdentifier:' . $item_values['system_identifier']]);
      $element[$delta] = [
        '#theme' => 'image',
        '#uri' => $data['link'],
        //'#width' => $item_values['width'],
        //'#height' => $item_values['height'],
        '#alt' => $search['APIResponse']['Items'][0]['CaptionLong'] ?? '',
        '#attributes' => [],
      ];
    }
    return $element;
  }

}
