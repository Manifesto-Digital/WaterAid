<?php

namespace Drupal\wateraid_donation_report\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the donation report config form.
 */
class DonationReportConfigForm extends ConfigFormBase {

  /**
   * Configuration settings.
   *
   * @var string
   */
  private const SETTINGS = 'wateraid_donation_report.config';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'donation_report_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::SETTINGS);

    // Email list form elements (and ajax 'add email field' logic).
    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base URL setting'),
      '#description' => $this->t('Please enter the Base URL. I.E (https://www.wateraid.org). This is used to create a link using the correct URL within the notification emails.'),
      '#default_value' => $config->get('base_url'),
    ];

    // Email list form elements (and ajax 'add email field' logic).
    $form['email_list'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Email addresses'),
      '#description' => $this->t('Use this page to add individual email addresses, Please add one email address per field, use the "Add Email Field" to add additional email fields.'),
    ];

    // Retrieve the existing email list and initialise the counter.
    $list = $config->get('email_list');

    if ($form_state->get('email_items_count') === NULL) {
      if (empty($list)) {
        $form_state->set('email_items_count', 1);
      }
      else {
        $form_state->set('email_items_count', count($list));
      }
    }

    // Define the fields based on what is stored in form state.
    $max = $form_state->get('email_items_count');

    $form['email_list']['email_list'] = [
      '#tree' => TRUE,
      '#prefix' => '<div id="list-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($delta = 0; $delta < $max; $delta++) {
      if (!isset($form['email_list']['email_list'][$delta])) {
        $element = [
          '#type' => 'email',
          '#default_value' => $list[$delta] ?? '',
        ];

        $form['email_list']['email_list'][$delta] = $element;
      }
    }

    // Define the add button.
    $form['email_list']['add'] = [
      '#type' => 'submit',
      '#name' => 'add',
      '#value' => $this->t('Add email field'),
      '#submit' => [[$this, 'addMoreSubmit']],
      '#ajax' => [
        'callback' => [$this, 'addMoreCallback'],
        'wrapper' => 'list-wrapper',
        'effect' => 'fade',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Let the form rebuild the email list email fields.
   *
   * @param mixed[] $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addMoreSubmit(array &$form, FormStateInterface $form_state): void {
    $count = $form_state->get('email_items_count');
    $count++;

    $form_state->set('email_items_count', $count);
    $form_state->setRebuild();
  }

  /**
   * Adds more email fields to the email list fieldset.
   *
   * @param mixed[] $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return mixed
   *   The AJAXed section of the form.
   */
  public function addMoreCallback(array &$form, FormStateInterface $form_state): mixed {
    return $form['email_list']['email_list'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Remove empty values from the email list.
    $list = [];

    foreach ($form_state->getValue('email_list') as $prefix) {
      if (!empty(trim($prefix))) {
        $list[] = $prefix;
      }
    }

    $form_state->setValue('email_list', $list);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config(static::SETTINGS)
      ->set('email_list', $form_state->getValue('email_list'))
      ->set('base_url', $form_state->getValue('base_url'))
      ->save();
  }

}
