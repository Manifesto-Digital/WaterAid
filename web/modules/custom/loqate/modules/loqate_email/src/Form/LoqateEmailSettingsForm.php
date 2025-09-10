<?php

namespace Drupal\loqate_email\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Loqate email integration settings form.
 */
class LoqateEmailSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'loqate_email.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'loqate_email_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('loqate_email.settings');

    $form['live_api_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Loqate Email API Key (live)'),
      '#default_value' => $config->get('live_api_key') ?: NULL,
    ];

    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mode'),
      '#options' => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
      ],
      '#default_value' => $config->get('mode') ?: 'test',
      '#description' => $this->t('When test mode is enabled, API calls to Loqate are skipped
        because sandbox mode is not available on the email verification endpoint. A message is displayed
        to end users stating that validation checks were skipped.'),
    ];

    $form['disable_globally'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable globally'),
      '#description' => $this->t('When checked, API calls are skipped regardless of the settings that
        have been administered on a field-by-field basis. Unlike "Test" mode, a message is not displayed to
        end users.'),
      '#default_value' => $config->get('disable_globally') ?: FALSE,
    ];

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#description' => $this->t('When checked, API calls are logged to watchdog. Do not enable on production.'),
      '#default_value' => $config->get('debug') ?: FALSE,
    ];

    $form['invalid_email_error_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Invalid email error message'),
      '#description' => $this->t('The error message users see when an invalid email address is supplied.'),
      '#default_value' => $config->get('invalid_email_error_message') ?: '',
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Loqate API response timeout (ms)'),
      '#min' => 1,
      '#max' => 15000,
      '#required' => TRUE,
      '#default_value' => $config->get('timeout') ?: 15000,
      '#description' => $this->t('The API response timeout in milliseconds.
        If the API call times out, this is treated as an invalid email address.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config('loqate_email.settings')
      ->set('mode', $form_state->getValue('mode'))
      ->set('live_api_key', $form_state->getValue('live_api_key'))
      ->set('disable_globally', $form_state->getValue('disable_globally'))
      ->set('invalid_email_error_message', $form_state->getValue('invalid_email_error_message'))
      ->set('timeout', $form_state->getValue('timeout'))
      ->set('debug', $form_state->getValue('debug'))
      ->save();

  }

}
