<?php

namespace Drupal\wateraid_base_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\ImageStyleStorageInterface;
use Drupal\media\Plugin\Field\FieldFormatter\MediaThumbnailFormatter;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ResponsiveMediaThumbnailFormatter.
 *
 * This custom formatter is extended from the Media plugin in core.
 * The original patch for discontinued contrib module media_entity
 * branch 8.x-1.x had the plugin patched & several other modules in
 * this project are heavily dependent on that patch.
 *
 * @see https://www.drupal.org/project/media_entity/issues/2863224
 *
 * @package Drupal\wateraid_base_core\Plugin\Field\FieldFormatter
 */
class ResponsiveMediaThumbnailFormatter extends MediaThumbnailFormatter {

  /**
   * Responsive image style storage.
   */
  protected EntityStorageInterface $responsiveImageStyleStorage;

  /**
   * Constructs an MediaThumbnailFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param mixed[] $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param mixed[] $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\image\ImageStyleStorageInterface $image_style_storage
   *   The image style entity storage handler.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $responsive_image_style_storage
   *   Responsive image style interface.
   */
  public function __construct(string $plugin_id, mixed $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, string $label, string $view_mode, array $third_party_settings, AccountInterface $current_user, ImageStyleStorageInterface $image_style_storage, FileUrlGeneratorInterface $file_url_generator, RendererInterface $renderer, EntityStorageInterface $responsive_image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage, $file_url_generator, $renderer);
    $this->responsiveImageStyleStorage = $responsive_image_style_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
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
      $container->get('renderer'),
      $container->get('entity_type.manager')->getStorage('responsive_image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'use_responsive_image' => FALSE,
      'responsive_image_style' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);

    $module_handler = \Drupal::service('module_handler');
    if ($module_handler->moduleExists('responsive_image')) {

      $element['image_style']['#states']['invisible'][':input[name$="[use_responsive_image]"]']['checked'] = TRUE;
      $element['image_style']['#weight'] = 0;

      $element['use_responsive_image'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Responsive image style'),
        '#weight' => -1,
        '#default_value' => $this->getSetting('use_responsive_image'),
      ];

      $responsive_styles = [];
      $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();
      if (!empty($responsive_image_styles)) {
        /** @var \Drupal\responsive_image\Entity\ResponsiveImageStyle $responsive_image_style */
        foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
          if ($responsive_image_style->hasImageStyleMappings()) {
            $responsive_styles[$machine_name] = $responsive_image_style->label();
          }
        }
      }

      $default_value = empty($this->getSetting('responsive_image_style')) ? 'landscape_image' : $this->getSetting('responsive_image_style');

      $element['responsive_image_style'] = [
        '#title' => $this->t('Responsive image style'),
        '#type' => 'select',
        '#default_value' => $default_value,
        '#options' => $responsive_styles,
        '#weight' => 0,
        '#states' => [
          'invisible' => [
            ':input[name$="[use_responsive_image]"]' => [
              'checked' => FALSE,
            ],
          ],
        ],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    $responsive_styles = [];
    $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();
    if (!empty($responsive_image_styles)) {
      /** @var \Drupal\responsive_image\Entity\ResponsiveImageStyle $responsive_image_style */
      foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
        if ($responsive_image_style->hasImageStyleMappings()) {
          $responsive_styles[$machine_name] = $responsive_image_style->label();
        }
      }
    }

    if ($this->getSetting('use_responsive_image')) {
      $summary = [];
      $responsive_image_style = $this->getSetting('responsive_image_style');
      if (isset($responsive_styles[$responsive_image_style])) {
        $summary[] = $this->t('Responsive image style: @style', ['@style' => $responsive_styles[$responsive_image_style]]);
      }
      else {
        $summary[] = $this->t('Original image');
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {

    if ($this->getSetting('use_responsive_image') !== TRUE) {
      return parent::viewElements($items, $langcode);
    }

    $elements = [];
    $media_items = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($media_items)) {
      return $elements;
    }

    $image_style_setting = $this->getSetting('responsive_image_style');

    /** @var \Drupal\media\MediaInterface[] $media_items */
    foreach ($media_items as $delta => $media) {
      $elements[$delta] = [
        '#theme' => 'responsive_image_formatter',
        '#item' => $media->get('thumbnail')->first(),
        '#item_attributes' => [],
        '#responsive_image_style_id' => $this->getSetting('responsive_image_style'),
        '#url' => $this->getMediaThumbnailUrl($media, $items->getEntity()),
      ];

      // Add cacheability of each item in the field.
      $this->renderer->addCacheableDependency($elements[$delta], $media);
    }

    // Add cacheability of the image style setting.
    if ($this->getSetting('image_link') && ($image_style = $this->responsiveImageStyleStorage->load($image_style_setting))) {
      $this->renderer->addCacheableDependency($elements, $image_style);
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();
    $style_id = $this->getSetting('responsive_image_style');
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($style_id && $style = ResponsiveImageStyle::load($style_id)) {
      $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies): bool {
    $changed = parent::onDependencyRemoval($dependencies);
    $style_id = $this->getSetting('image_style');
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($style_id && $style = ResponsiveImageStyle::load($style_id)) {
      if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
        $replacement_id = NULL;
        // If a valid replacement has been provided in the storage, replace the
        // image style with the replacement and signal that the formatter plugin
        // settings were updated.
        if ($replacement_id && ResponsiveImageStyle::load($replacement_id)) {
          $this->setSetting('responsive_image_style', $replacement_id);
          $changed = TRUE;
        }
      }
    }
    return $changed;
  }

}
