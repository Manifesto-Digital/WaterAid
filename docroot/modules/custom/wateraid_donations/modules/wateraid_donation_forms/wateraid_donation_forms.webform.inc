<?php

/**
 * @file
 * Donation form webform hook module.
 */

use Drupal\Component\Utility\Environment;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Implements hook_webform_element_alter().
 */
function wateraid_donation_forms_webform_element_alter(array &$element, FormStateInterface $form_state, array $context): void {
  // Add custom data attributes to a specific element type.
  $element['#after_build'][] = 'wateraid_donation_forms_webform_composite_element_after_build';
}

/**
 * After build callback for adding classes to webform composite elements.
 *
 * @param mixed[] $element
 *   Element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form state.
 *
 * @return mixed[]
 *   The element array.
 */
function wateraid_donation_forms_webform_composite_element_after_build(array $element, FormStateInterface $form_state): array {
  $children = Element::children($element);

  if (!empty($element['#webform_composite'])) {

    $visible_children = [];

    // Add visible elements class.
    foreach ($children as $key) {
      $access_key = '#' . $key . '__access';
      if (!isset($element[$access_key]) || $element[$access_key] !== FALSE) {
        $visible_children[] = $key;
      }

      // Add composite sub element key classes.
      if (!empty($element[$key]['#theme_wrappers'])) {
        $wrapper_class = HTML::cleanCssIdentifier('wa-subelement-wrapper-' . $key);
        if (!isset($element[$key]['#wrapper_attributes']['class'])) {
          $element[$key]['#wrapper_attributes']['class'] = [];
        }
        $element[$key]['#wrapper_attributes']['class'] = array_merge($element[$key]['#wrapper_attributes']['class'], [$wrapper_class]);

      }

      RenderElement::setAttributes($element[$key], [HTML::cleanCssIdentifier('wa-subelement-' . $key)]);
    }

    // Composite classes to allow different styling for different selections of
    // visible composite sub elements.
    RenderElement::setAttributes($element,
      [
        HTML::cleanCssIdentifier('wa-composite-' . implode('-', $visible_children)),
      ]
    );

  }

  // Add included sub elements class and composite type class.
  if (isset($element['#type'])) {
    RenderElement::setAttributes($element, [HTML::cleanCssIdentifier('wa-element-type-' . $element['#type'])]);
  }

  if (!empty($element['#theme_wrappers'])) {
    isset($element['#type']) ? $wrapper_class = HTML::cleanCssIdentifier('wa-element-wrapper-' . $element['#type']) : $wrapper_class = 'wa-element-wrapper';

    if (!isset($element['#wrapper_attributes']['class'])) {
      $element['#wrapper_attributes']['class'] = [];
    }
    $element['#wrapper_attributes']['class'] = array_merge($element['#wrapper_attributes']['class'], [$wrapper_class]);
  }

  return $element;
}

/**
 * Implements hook_webform_third_party_settings_form_alter().
 *
 * Donation third party settings, please also note that these are overridden if
 * a ConfirmationPageWebformHandler is configured and set to override these
 * settings.
 *
 * @see \Drupal\wateraid_donation_forms\Controller\WebformController::applyDonationConfirmationSettings()
 */
function wateraid_donation_forms_webform_third_party_settings_form_alter(&$form, FormStateInterface $form_state): void {

  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $form_state->getFormObject()->getEntity();
  /** @var \Drupal\currency\FormHelperInterface $form_helper */
  $form_helper = \Drupal::service('currency.form_helper');

  $form['third_party_settings']['wateraid_donation_forms'] = [
    '#type' => 'details',
    '#title' => t('Donation form settings'),
    '#open' => TRUE,
    '#weight' => 100,
  ];

  $form['third_party_settings']['wateraid_donation_forms']['currency'] = [
    '#type' => 'select',
    '#title' => t('Currency'),
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'currency') ?? '',
    '#options' => $form_helper->getCurrencyOptions(),
  ];

  $form['third_party_settings']['wateraid_donation_forms']['donation_upsell_confirmation'] = [
    '#type' => 'details',
    '#title' => t('Donation Upsell Confirmation Settings'),
    '#open' => FALSE,
  ];

  $form['third_party_settings']['wateraid_donation_forms']['donation_upsell_confirmation']['donation_upsell_image'] = [
    '#type' => 'managed_file',
    '#title' => t('Upsell Image'),
    '#description' => t('This image is displayed in the left column on desktop, and at the top of the page on mobile'),
    '#upload_location' => 'public://',
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'donation_upsell_confirmation')['donation_upsell_image'] ?? [],
    '#upload_validators' => [
      'file_validate_extensions' => ['gif png jpg jpeg'],
      'file_validate_size' => [Environment::getUploadMaxSize()],
    ],
    '#required' => FALSE,
  ];

  $form['third_party_settings']['wateraid_donation_forms']['donation_upsell_confirmation']['donation_upsell_text'] = [
    '#type' => 'text_format',
    '#title' => t('Upsell Text'),
    '#description' => t('Displayed in the right column'),
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'donation_upsell_confirmation')['donation_upsell_text']['value'] ?? '',
    '#format' => $webform->getThirdPartySetting('wateraid_donation_forms', 'donation_upsell_confirmation')['donation_upsell_text']['format'],
  ];

  $form['third_party_settings']['wateraid_donation_forms']['donation_upsell_confirmation']['donation_upsell_discount_widget'] = [
    '#type' => 'fieldset',
    '#title' => t('Discount Widget'),
    '#description' => t('A widget that allows the user to copy a code to cilpboard, then navigate to the shop'),
  ];

  $form['third_party_settings']['wateraid_donation_forms']['donation_upsell_confirmation']['donation_upsell_discount_widget']['donation_upsell_discount_code'] = [
    '#type' => 'textfield',
    '#title' => t('Shop Discount Code'),
    '#description' => t('To display the shop code widget, add a discount code here'),
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'donation_upsell_confirmation')['donation_upsell_discount_widget']['donation_upsell_discount_code'] ?? '',
  ];

  $form['third_party_settings']['wateraid_donation_forms']['donation_upsell_confirmation']['donation_upsell_discount_widget']['donation_upsell_exclusions'] = [
    '#type' => 'textfield',
    '#title' => t('Exclusions'),
    '#description' => t('Text displayed by the shop code widget for clarifications about the discount code'),
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'donation_upsell_confirmation')['donation_upsell_discount_widget']['donation_upsell_exclusions'] ?? '',
  ];

  $form['third_party_settings']['wateraid_donation_forms']['donation_confirmation'] = [
    '#type' => 'text_format',
    '#title' => t('Display the donation confirmation'),
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'donation_confirmation')['value'] ?? '',
  ];

  $form['third_party_settings']['wateraid_donation_forms']['gift_aid_confirmation'] = [
    '#type' => 'text_format',
    '#title' => t('Display the Gift Aid confirmation'),
    '#description' => t('Set the text to display on the confirmation page when the Gift Aid option is checked.'),
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'gift_aid_confirmation')['value'] ?? '',
  ];

  $form['third_party_settings']['wateraid_donation_forms']['gift_aid_email_confirmation'] = [
    '#type' => 'text_format',
    '#title' => t('Display the Gift Aid email confirmation'),
    '#description' => t('Set the text to display on the confirmation email when the Gift Aid option is checked.'),
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'gift_aid_email_confirmation')['value'] ?? '',
  ];

  $form['third_party_settings']['wateraid_donation_forms']['social_share_title'] = [
    '#type' => 'text_format',
    '#title' => t('Social Share title'),
    '#description' => t('Set the text that will appear above the social share icons'),
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'social_share_title')['value'] ?? '',
  ];

  $form['third_party_settings']['wateraid_donation_forms']['social_share_text'] = [
    '#type' => 'textfield',
    '#title' => t('Social Share text'),
    '#description' => t('Set the text that will appear when a user shares the confirmation page.'),
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'social_share_text') ?? '',
  ];

  $form['third_party_settings']['wateraid_donation_forms']['after_confirmation_footer'] = [
    '#type' => 'text_format',
    '#title' => t('Display the confirmation footer'),
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'after_confirmation_footer')['value'] ?? '',
  ];

  $form['third_party_settings']['wateraid_donation_forms']['after_confirmation_cta'] = [
    '#type' => 'text_format',
    '#title' => t('Display after confirmation CTA'),
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'after_confirmation_cta')['value'] ?? '',
  ];

  $form['third_party_settings']['wateraid_donation_forms']['direct_debit_guarantee'] = [
    '#type' => 'text_format',
    '#title' => t('Direct debit guarantee text'),
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'direct_debit_guarantee')['value'] ?? '',
  ];

  $form['third_party_settings']['wateraid_donation_forms']['bank_transfer_instructions'] = [
    '#type' => 'text_format',
    '#title' => t('Bank account transfer payment confirmation text'),
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'bank_transfer_instructions')['value'] ?? '',
    '#description' => t('Text to appear on the confirmation page if the bank transfer payment method is selected.'),
  ];

  $form['third_party_settings']['wateraid_donation_forms']['confirmation_image_url'] = [
    '#type' => 'textfield',
    '#title' => t('Confirmation Image URL'),
    '#description' => t('The URL of a custom image to be displayed on the submission confirmation page. If left blank, the default image will be used.'),
    '#default_value' => $webform->getThirdPartySetting('wateraid_donation_forms', 'confirmation_image_url') ?? '',
  ];

  if ($webform->getThirdPartySetting('wateraid_donation_forms', 'confirmation_image_url')) {
    $form['third_party_settings']['wateraid_donation_forms']['confirmation_image'] = [
      '#theme' => 'image',
      '#uri' => $webform->getThirdPartySetting('wateraid_donation_forms', 'confirmation_image_url'),
      '#alt' => t('Completed donation image'),
      '#attributes' => [
        'style' => 'height:210px',
      ],
    ];
  }
}
