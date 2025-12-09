<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
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
final class DamImageFormater extends ImageFormatter {


  /**
   * Constructs an ImageFormatter object.
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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
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
    AccountInterface $current_user,
    EntityStorageInterface $image_style_storage,
    FileUrlGeneratorInterface $file_url_generator,
    private readonly Api $orangeDamApi,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage, $file_url_generator);
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
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('file_url_generator'),
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
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    $url = NULL;
    $link_file = FALSE;

    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');
    $width = $height = NULL;

    // Collect cache tags to be added for each item in the field.
    $cache = [];
    if (!empty($image_style_setting)) {
      /** @var \Drupal\image\ImageStyleInterface $image_style */
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache['tags'] = $image_style->getCacheTags();
      if ($effects = $image_style->getEffects()) {
        foreach ($effects as $effect) {
          $config = $effect->getConfiguration();

          $width = ($config['data']['width']) ?? NULL;
          $height = ($config['data']['height']) ?? NULL;
        }
      }
    }

    foreach ($items as $delta => $item) {
      $item_values = $item->getValue();

      if (!$height) {
        $height = $item_values['height'];
      }
      if (!$width) {
        $width = $item_values['width'];
      }

      $data = $this->orangeDamApi->getPublicLink($item_values['system_identifier'], NULL, $width, $height, $link_file);

      if (isset($data['expirationDate'])) {
        if ($max_age = $this->calculateMaxAge($data['expirationDate'])) {
          $cache['max-age'] = $max_age;
        }
      }

      $search = $this->orangeDamApi->search(['query' => 'SystemIdentifier:' . $item_values['system_identifier']]);
      $element[$delta] = [
        '#theme' => 'image',
        '#uri' => $data['link'],
        '#width' => $width,
        '#height' => $height,
        '#alt' => Html::escape(substr($search['APIResponse']['Items'][0]['CustomField.Caption'], 0, 250)) ?? '',
        '#attributes' => [],
        '#cache' => $cache,
      ];
    }

    return $element;
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
