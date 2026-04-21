<?php

declare(strict_types=1);

namespace Drupal\wa_crm_logs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure CRM Logs settings for this site.
 */
final class CRMLogSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wa_crm_logs_crm_log_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['wa_crm_logs.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['expiry'] = [
      '#type' => 'number',
      '#title' => $this->t('Delete logs after'),
      '#description' => t('Enter a number of <strong>days</strong>. When a log is this many days old it will be deleted. Leave empty to disable.'),
      '#default_value' => $this->config('wa_crm_logs.settings')->get('expiry'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $value = $form_state->getValue('expiry') ?? NULL;

    $this->config('wa_crm_logs.settings')
      ->set('expiry', $value)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
