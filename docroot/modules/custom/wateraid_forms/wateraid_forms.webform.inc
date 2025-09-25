<?php

/**
 * @file
 * WaterAid webform hooks.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\wateraid_forms\WateraidFormsUrl;

/**
 * Implements hook_webform_admin_third_party_settings_form_alter().
 */
function wateraid_forms_webform_admin_third_party_settings_form_alter(&$form, FormStateInterface $form_state): void {
  /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
  $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');
  $default_url_parameters = implode(PHP_EOL, WateraidFormsUrl::WATERAID_URL_PARAMETERS);
  $url_parameters = $third_party_settings_manager->getThirdPartySetting('wateraid_forms', 'url_parameters', $default_url_parameters);

  $form['third_party_settings']['wateraid_forms'] = [
    '#type' => 'details',
    '#title' => t('WaterAid cookie and url parameters'),
    '#open' => TRUE,
  ];

  $form['third_party_settings']['wateraid_forms']['url_parameters'] = [
    '#type' => 'textarea',
    '#title' => t('URL parameters'),
    '#description' => t('List of url parameters to save in a cookie and include in form submissions. Enter one parameter key per line.'),
    '#default_value' => $url_parameters,
  ];
}
