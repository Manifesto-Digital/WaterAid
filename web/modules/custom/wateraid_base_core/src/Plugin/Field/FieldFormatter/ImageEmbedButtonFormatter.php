<?php

namespace Drupal\wateraid_base_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'image_credit_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "image_embed_button_formatter",
 *   label = @Translation("WA image embed button"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   allowed_responsive_image_styles = {
 *     "original_ratio",
 *     "landscape_image",
 *     "portrait_image",
 *     "small_square_image"
 *   }
 * )
 */
class ImageEmbedButtonFormatter extends WAImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'use_responsive_image' => TRUE,
      'responsive_image_style' => '',
      'image_link' => 'landscape_image',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);

    // Only use responsive image styles.
    unset($form['image_style']);

    // Never allow linking from the image.
    unset($form['image_link']);

    // Always use default value of 'use_responsive_image' => TRUE.
    unset($form['use_responsive_image']);

    // Remove any responsive image styles that we do not allow to be selected.
    foreach ($form['responsive_image_style']['#options'] as $key => $option) {
      if (!in_array($key, $this->getPluginDefinition()['allowed_responsive_image_styles'])) {
        unset($form['responsive_image_style']['#options'][$key]);
      }
    }

    $entity = $form_state->get('entity');
    $edit_url = $entity->toUrl('edit-form');

    $form['edit_link_label'] = [
      '#type' => 'label',
      '#title' => $this->t('Edit Image'),
    ];

    $form['edit_link'] = [
      '#title' => $entity->get('name')->getValue()[0]['value'],
      '#type' => 'link',
      '#url' => $edit_url,
      '#attributes' => [
        'title' => $this->t('Edit image'),
        'target' => '_blank',
      ],
      '#suffix' => '<br>',
    ];

    $form['edit_link_description'] = [
      '#markup' => $this->t('This will open up in a new window where you can change the focal point and credit/caption information.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = parent::viewElements($items, $langcode);
    $media = $this->getEntitiesToView($items, $langcode);

    /** @var \Drupal\media\MediaInterface $media_item */
    foreach ($media as $delta => $media_item) {
      if (isset($elements[$delta])) {
        $el = [];

        /* Temporary measure to lazy-load images using the new loading
        attribute provided by Drupal Core 9.4.

        This replaces lazy loading functionality previously achieved
        with the "Blazy" text filter which does not work correctly in
        conjunction with embedded media entities and responsive image
        styles.

        Once https://www.drupal.org/project/drupal/issues/3192234 is
        resolved, this override can be removed allowing the user specify
        whether images should be lazy loaded or not on a per-embed basis.*/
        $elements[$delta]['#item_attributes']['loading'] = 'lazy';

        if ($media_item->hasField('field_media_caption') && $this->getSetting('show_caption')) {
          $value = $media_item->get('field_media_caption')->getValue();
          if (!empty($value)) {
            // Render through the field template system, so we pick up the
            // correct wrappers.
            $el['media_caption'] = $media_item->get('field_media_caption')->view(
              [
                'label' => 'hidden',
                'weight' => '0',
              ]
            );
          }
        }
        if ($media_item->hasField('field_media_credit') && $this->getSetting('show_credit')) {
          $value = $media_item->get('field_media_credit')->getValue();
          if (!empty($value)) {
            // Render through the field template system, so we pick up the
            // correct wrappers.
            $el['media_credit'] = $media_item->get('field_media_credit')->view(
              [
                'label' => 'hidden',
                'weight' => '1',
              ]
            );
          }
        }

        if ($el) {
          $output = \Drupal::service('renderer')->render($el);
          $elements[$delta]['#suffix'] = '<div class="media-info-wrapper">' . $output . '</div>';
        }
      }
    }

    return $elements;
  }

}
