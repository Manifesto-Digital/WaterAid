<?php

namespace Drupal\webform_capture_plus\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LoqateApiAdminForm.
 *
 * Contains admin form functionality for the Loqate API.
 */
class LoqateApiAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'loqate_api_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'webform_capture_plus.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('webform_capture_plus.settings');

    $form['test_api_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Loqate API Key (test)'),
      '#default_value' => $config->get('test_api_key'),
    ];

    $form['live_api_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Loqate API Key (live)'),
      '#default_value' => $config->get('live_api_key'),
    ];
    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mode'),
      '#options' => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
      ],
      '#default_value' => $config->get('mode'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('webform_capture_plus.settings')
      ->set('mode', $form_state->getValue('mode'))
      ->set('test_api_key', $form_state->getValue('test_api_key'))
      ->set('live_api_key', $form_state->getValue('live_api_key'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
