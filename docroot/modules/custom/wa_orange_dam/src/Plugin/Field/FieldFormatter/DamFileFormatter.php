<?php

namespace Drupal\wa_orange_dam\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\file\Plugin\Field\FieldFormatter\BaseFieldFileFormatterBase;
use Drupal\wa_orange_dam\Service\Api;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for file formatters, which allow to link to the file download URL.
 */
#[FieldFormatter(
  id: 'wa_orange_dam_file_formater',
  label: new TranslatableMarkup('Dam File'),
  description: new TranslatableMarkup('Display the file.'),
  field_types: [
    'wa_orange_dam_file',
  ],
)]
final class DamFileFormatter extends BaseFieldFileFormatterBase {

  /**
   * Constructs a BaseFieldFileFormatterBase object.
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
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
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
    FileUrlGeneratorInterface $file_url_generator,
    private readonly Api $orangeDamApi,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $file_url_generator);
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
      $container->get('file_url_generator'),
      $container->get('wa_orange_dam.api'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    $item_values = $item->getValue();
    $download = ($this->getSetting('file_download_path')) ?? FALSE;
    $data = $this->orangeDamApi->getPublicLink($item_values['system_identifier'], NULL, NULL, NULL, $download);

    return $data['link'];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $url = $this->getSetting('link_to_file');

    foreach ($items as $delta => $item) {
      $view_value = $this->viewValue($item);

      if ($url) {

        $uri = Url::fromUri($view_value);
        $link = Link::fromTextAndUrl($view_value, $uri);

        $elements[$delta] = $link->toRenderable();
      }
      else {
        $elements[$delta] = [
          '#markup' => $view_value,
        ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    return TRUE;
  }

}
