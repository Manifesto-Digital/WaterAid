<?php

namespace Drupal\webform_bankaccount\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Loqate Api Settings Form.
 */
class LoqateApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'webform_bankaccount.loqateapisettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'loqate_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('webform_bankaccount.loqateapisettings');

    $loqate_api_url = Url::fromUri('https://www.loqate.com/resources/support/apis/');
    $api_description_link = Link::fromTextAndUrl($this->t('Loqate APIs'), $loqate_api_url)->toString();
    $api_description = $this->t('Read more about @loqate_api_link', ["@loqate_api_link" => $api_description_link]);
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api Key'),
      '#description' => $api_description,
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('api_key'),
    ];

    $loqate_bank_api_url = Url::fromUri('https://www.loqate.com/resources/support/apis/bankaccountvalidation/');
    $bank_api_description_link = Link::fromTextAndUrl($this->t('Loqate Bank verification APIs'), $loqate_bank_api_url)->toString();
    $bank_api_description = $this->t('Read more about @loqate_bank_api_link', ["@loqate_bank_api_link" => $bank_api_description_link]);
    $form['bank_verification_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bank Verification Api Url'),
      '#description' => $bank_api_description,
      '#maxlength' => 255,
      '#size' => 100,
      '#default_value' => $config->get('bank_verification_api_url'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config('webform_bankaccount.loqateapisettings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('bank_verification_api_url', $form_state->getValue('bank_verification_api_url'))
      ->save();
  }

}
