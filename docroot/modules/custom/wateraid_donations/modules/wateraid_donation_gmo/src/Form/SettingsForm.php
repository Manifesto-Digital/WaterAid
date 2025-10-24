<?php

namespace Drupal\wateraid_donation_gmo\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure WaterAid Donations GMO settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wateraid_donation_gmo_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['wateraid_donation_gmo.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('wateraid_donation_gmo.settings');

    $form['live'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Live'),
    ];
    $form['live']['shop_id_live'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shop ID (Live)'),
      '#default_value' => $config->get('shop_id_live') ?? '',
      '#description' => $this->t('Shop ID for live payments.'),
    ];
    $form['live']['salesforce_url_live'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SalesForce payment request URL (Live)'),
      '#default_value' => $config->get('salesforce_url_live') ?? '',
      '#description' => $this->t('The URL to submit payment requests to (e.g. https://{instance}.salesforce-sites.com/paymentrequest).'),
    ];
    $form['live']['salesforce_token_live'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SalesForce payment token (Live)'),
      '#default_value' => $config->get('salesforce_token_live') ?? '',
      '#description' => $this->t('The token to send when posting to the payment request URL.'),
    ];

    $form['test'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Test'),
    ];
    $form['test']['shop_id_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shop ID (Test)'),
      '#default_value' => $config->get('shop_id_test') ?? '',
      '#description' => $this->t('Shop ID for test payments.'),
    ];
    $form['test']['salesforce_url_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SalesForce payment request URL (Test)'),
      '#default_value' => $config->get('salesforce_url_test') ?? '',
      '#description' => $this->t('The URL to submit payment requests to (e.g. https://{instance}.salesforce-sites.com/paymentrequest).'),
    ];
    $form['test']['salesforce_token_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SalesForce payment token (Test)'),
      '#default_value' => $config->get('salesforce_token_test') ?? '',
      '#description' => $this->t('The token to send when posting to the payment request URL.'),
    ];

    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mode'),
      '#options' => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
      ],
      '#default_value' => $config->get('mode') ?? 'test',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('wateraid_donation_gmo.settings')
      ->set('shop_id_live', $form_state->getValue('shop_id_live'))
      ->set('salesforce_url_live', $form_state->getValue('salesforce_url_live'))
      ->set('salesforce_token_live', $form_state->getValue('salesforce_token_live'))
      ->set('shop_id_test', $form_state->getValue('shop_id_test'))
      ->set('salesforce_url_test', $form_state->getValue('salesforce_url_test'))
      ->set('salesforce_token_test', $form_state->getValue('salesforce_token_test'))
      ->set('mode', $form_state->getValue('mode'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
