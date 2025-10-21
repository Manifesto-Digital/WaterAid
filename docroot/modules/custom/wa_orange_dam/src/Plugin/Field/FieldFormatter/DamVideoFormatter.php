<?php

namespace Drupal\wa_orange_dam\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\file\Plugin\Field\FieldFormatter\FileVideoFormatter;
use Drupal\wa_orange_dam\Service\Api;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'file_video' formatter.
 */
#[FieldFormatter(
  id: 'wa_orange_dam_video_formater',
  label: new TranslatableMarkup('Dam Video'),
  description: new TranslatableMarkup('Display the file using an HTML5 video tag.'),
  field_types: [
    'wa_orange_dam_video',
  ],
)]
final class DamVideoFormatter extends FileVideoFormatter {


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
  protected function needsEntityLoad($item): bool {

    // This isn't an entity reference field, so entities cannot be loaded.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = $source_files = [];

    foreach ($items as $delta => $item) {
      $item_values = $item->getValue();
      $max_age = NULL;

      $data = $this->orangeDamApi->getPublicLink($item_values['system_identifier']);

      if (isset($data['expirationDate'])) {
        $max_age = $this->calculateMaxAge($data['expirationDate']);
      }

      $mime = isset($data['fileExtension']) ? 'video/' . $data['fileExtension'] : 'video/mp4';


      $attributes = new Attribute();
      $attributes->setAttribute('src', $data['link']);
      $attributes->setAttribute('type', $mime);

      $source_files[] = [
        [
          'file' => '',
          'source_attributes' => $attributes,
          'cache' => $max_age,
        ],
      ];
    }

    if (empty($source_files)) {
      return $elements;
    }

    $attributes = $this->prepareAttributes();

    foreach ($source_files as $delta => $files) {
      $elements[$delta] = [
        '#theme' => 'file_video',
        '#attributes' => $attributes,
        '#files' => $files,
      ];

      if ($files[0]['cache']) {
        $elements[$delta]['#cache']['max-age'] = $files[0]['cache'];
      }
    }

    return $elements;
  }

  /**
   * Calculate the number of seconds between now and the expiry.
   *
   * @param string $expiration_date
   *   The expiry string returned by the API.
   *
   * @return int|null
   *   The number of seconds or NULL on error.
   */
  private function calculateMaxAge(string $expiration_date): ?int {
    $return = NULL;

    try {
      $now = new DrupalDateTime();
      $expiry = DrupalDateTime::createFromFormat('YYYY-MM-DDTHH:MM:SS.SSSZ', $expiration_date);

      $return = $expiry->getTimestamp() - $now->getTimestamp();
    }
    catch (\Exception $e) {

      // Something went wrong. We'll ignore and return the default.
    }

    return $return;
  }

}
