<?php

namespace Drupal\wateraid_base_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Custom field formatter plugin for rendering images.
 *
 * The need for this custom field formatter comes from a couple of requirements:
 * 1) Allowing the credit and/or caption field to be added to the display.
 * 2) The media module doesn't actually ship with its own field formatter.  All
 * they provide is the "Thumbnail" formatter, which is not suitable (e.g. it
 * doesn't render the correct alt tags).  We extend the thumbnail formatter and
 * fix things. See: https://www.drupal.org/node/2850169
 *
 * @FieldFormatter(
 *   id = "wa_image_formatter",
 *   label = @Translation("Wateraid Image Formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class WAImageFormatter extends ResponsiveMediaThumbnailFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'show_credit' => FALSE,
      'show_caption' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);

    $element['show_credit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show credit'),
      '#default_value' => $this->getSetting('show_credit'),
    ];

    $element['show_caption'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show caption'),
      '#default_value' => $this->getSetting('show_caption'),
    ];

    return $element;
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
        // As we don't know what the media source field is called we need to
        // grab this from the configuration.
        $source_field_name = $media_item->getSource()->getConfiguration()['source_field'];

        $elements[$delta]['#item'] = $media_item->get($source_field_name);

        $el = [];

        if ($this->getSetting('show_caption') && $media_item->hasField('field_media_caption')) {
          $value = $media_item->get('field_media_caption')->getValue();
          if (!empty($value)) {
            // Render through the field template system, so we pick up the
            // correct wrappers.
            $el['media_caption'] = $media_item->get('field_media_caption')->view(
              [
                'label' => 'hidden',
                'weight' => '1',
              ]
            );
          }
        }

        if ($this->getSetting('show_credit') && $media_item->hasField('field_media_credit')) {
          $value = $media_item->get('field_media_credit')->getValue();
          if (!empty($value)) {
            // Render through the field template system, so we pick up the
            // correct wrappers.
            $el['media_credit'] = $media_item->get('field_media_credit')->view(
              [
                'label' => 'hidden',
                'weight' => '0',
              ]
            );
          }
        }

        if ($el) {
          $output = \Drupal::service('renderer')->render($el);
          $elements[$delta]['#suffix'] = $output;
        }
      }
    }

    return $elements;
  }

}
