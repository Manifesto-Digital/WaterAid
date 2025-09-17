<?php

namespace Drupal\webform_capture_plus;

use Drupal\Core\Form\FormStateInterface;

/**
 * Webform Address CapturePlus Trait.
 *
 * @package Drupal\webform_capture_plus
 */
trait WebformAddressCapturePlusTrait {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties(): array {
    return [
      WebformAddressCapturePlusConstants::WEBFORM_CAPTURE_PLUS_ACTIVE => FALSE,
      WebformAddressCapturePlusConstants::WEBFORM_CAPTURE_LOOKUP_LABEL => '',
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Add the new form elements so the user can specify whether they want the
    // capture plus activated for this element or not.
    $form['capture_plus'] = [
      '#type' => 'details',
      '#title' => $this->t('Capture Plus'),
      '#access' => TRUE,
    ];

    $form['capture_plus'][WebformAddressCapturePlusConstants::WEBFORM_CAPTURE_PLUS_ACTIVE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate Capture Plus'),
      '#access' => TRUE,
    ];

    $form['capture_plus'][WebformAddressCapturePlusConstants::WEBFORM_CAPTURE_LOOKUP_LABEL] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override lookup Label'),
      '#description' => $this->t('Defaults to "Search for your address…"'),
      '#states' => [
        'invisible' => [
          ':input[name="' . WebformAddressCapturePlusConstants::WEBFORM_CAPTURE_PLUS_ACTIVE . '"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
      '#access' => TRUE,
    ];

    $form['capture_plus'][WebformAddressCapturePlusConstants::WEBFORM_CAPTURE_PLACEHOLDER] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override placeholder text'),
      '#description' => $this->t('Defaults to "Start typing your address or postcode"'),
      '#states' => [
        'invisible' => [
          ':input[name="' . WebformAddressCapturePlusConstants::WEBFORM_CAPTURE_PLUS_ACTIVE . '"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
      '#access' => TRUE,
    ];

    return $form;
  }

}
