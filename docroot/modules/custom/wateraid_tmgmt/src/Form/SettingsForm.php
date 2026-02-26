<?php

namespace Drupal\wateraid_tmgmt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure WaterAid TMGMT settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wateraid_tmgmt_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['wateraid_tmgmt.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['email'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Email settings'),
    ];

    $form['email']['enable_email_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable email notifications'),
      '#description' => $this->t('When enabled, email notifications will be sent to translation job authors if errors occur or the job has not been successfully processed within 1 week.'),
      '#default_value' => $this->config('wateraid_tmgmt.settings')->get('enable_email_notifications') ?? TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('wateraid_tmgmt.settings')
      ->set('enable_email_notifications', $form_state->getValue('enable_email_notifications'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
