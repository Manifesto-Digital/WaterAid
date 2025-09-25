<?php

namespace Drupal\wateraid_donation_paypal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class PayPalApiAdminForm.
 *
 * Contains admin form functionality for the PayPal API.
 */
class PayPalApiAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'paypal_api_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'wateraid_donation_paypal.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('wateraid_donation_paypal.settings');

    // @see https://www.drupal.org/docs/7/api/localization-api/dynamic-or-static-links-and-html-in-translatable-strings
    $form['test_secret_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('PayPal Secret Key (test)'),
      '#default_value' => $config->get('test_secret_key'),
    ];
    $form['test_public_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('PayPal Public Key (test)'),
      '#default_value' => $config->get('test_public_key'),
    ];
    $form['live_secret_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('PayPal Secret Key (live)'),
      '#default_value' => $config->get('live_secret_key'),
    ];
    $form['live_public_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('PayPal Public Key (live)'),
      '#default_value' => $config->get('live_public_key'),
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

    $form['webhook_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Webhook URL'),
      '#default_value' => Url::fromRoute('paypal_api.webhook', [], ['absolute' => TRUE])
        ->toString(),
      '#description' => $this->t('Add this webhook path in the <a href="@paypal-dashboard">PayPal Dashboard</a>', [
        '@paypal-dashboard' => Url::fromUri('https://developer.paypal.com/developer/applications/edit/', ['attributes' => ['target' => '_blank']])->toString(),
      ]),
    ];

    $form['log_webhooks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log incoming webhooks'),
      '#default_value' => $config->get('log_webhooks'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('wateraid_donation_paypal.settings')
      ->set('mode', $form_state->getValue('mode'))
      ->set('log_webhooks', $form_state->getValue('log_webhooks'))
      ->set('test_secret_key', $form_state->getValue('test_secret_key'))
      ->set('test_public_key', $form_state->getValue('test_public_key'))
      ->set('live_secret_key', $form_state->getValue('live_secret_key'))
      ->set('live_public_key', $form_state->getValue('live_public_key'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
