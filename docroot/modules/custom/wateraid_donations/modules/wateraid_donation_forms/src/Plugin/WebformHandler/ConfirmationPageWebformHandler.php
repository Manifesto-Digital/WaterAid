<?php

namespace Drupal\wateraid_donation_forms\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\wateraid_donation_forms\FallbackWebformHandlerTrait;
use Drupal\wateraid_donation_forms\PaymentFrequencyWebformHandlerTrait;
use Drupal\webform\Plugin\WebformHandlerBase;

/**
 * Handler for the Confirmation Page.
 *
 * @package Drupal\wateraid_donation_forms\Plugin\WebformHandler
 *
 * @WebformHandler(
 *   id = "confirmation_page",
 *   label = @Translation("Confirmation Page"),
 *   category = @Translation("Other"),
 *   description = @Translation("Directs a webform submission via a confirmation page."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 *
 * @see \Drupal\wateraid_donation_forms\Controller\WebformController::applyDonationConfirmationPageHandlerSettings()
 */
class ConfirmationPageWebformHandler extends WebformHandlerBase {

  use PaymentFrequencyWebformHandlerTrait, FallbackWebformHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + $this->getPaymentFrequencyDefaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Confirmation settings overrides.
    $form['confirmation_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation settings'),
    ];

    $form['confirmation_settings']['confirmation_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation title'),
      '#description' => $this->t('Page title to be shown upon successful submission.'),
      '#default_value' => $this->configuration['confirmation_title'] ?? NULL,
    ];

    $warnings = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#title' => 'Warning',
      '#items' => [
        $this->t('These fields will override the "Confirmation settings" under tab "Settings > Confirmation" of this Webform.'),
      ],
    ];

    $form['confirmation_settings']['confirmation_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Confirmation message'),
      '#description' => $this->t('Message to be shown upon successful submission.'),
      '#default_value' => $this->configuration['confirmation_message'] ?? NULL,
      '#suffix' => $this->renderer->render($warnings),
    ];

    $form['confirmation_settings']['token_tree_link'] = $this->buildTokenTreeElement();

    $form = $this->buildFallbackForm($form, $form_state, 'Confirmation message');

    // 3rd Party donation form settings overrides.
    $form['wateraid_donation_forms'] = [
      '#type' => 'details',
      '#title' => $this->t('Donation form settings'),
    ];

    $warnings = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#title' => 'Warning',
      '#items' => [
        $this->t('These fields will override the "Donation form settings" under tab "Settings > General > Third party settings" of this Webform.'),
      ],
    ];

    $form['wateraid_donation_forms']['donation_confirmation'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Display the donation confirmation'),
      '#default_value' => $this->configuration['donation_confirmation'] ?? NULL,
      '#suffix' => $this->renderer->render($warnings),
    ];

    $form['wateraid_donation_forms']['token_tree_link'] = $this->buildTokenTreeElement();

    return $this->buildPaymentFrequencyForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    // Confirmation settings.
    $this->configuration['confirmation_title'] = $form_state->getValue([
      'confirmation_settings',
      'confirmation_title',
    ]) ?: NULL;
    $this->configuration['confirmation_message'] = $form_state->getValue([
      'confirmation_settings',
      'confirmation_message',
    ]) ?: NULL;
    // Fallback settings.
    $this->configuration['fallback_plugin'] = $form_state->getValue([
      'fallback_settings',
      'fallback_plugin',
    ]) ?: NULL;
    $this->configuration['fallback_message'] = $form_state->getValue([
      'fallback_settings',
      'fallback_wrapper',
      'fallback_message',
    ]) ?: NULL;
    // Donation form settings.
    $this->configuration['donation_confirmation'] = $form_state->getValue([
      'wateraid_donation_forms',
      'donation_confirmation',
    ]) ?: NULL;
    // Payment frequency.
    $this->configuration['payment_frequency'] = $form_state->getValue('payment_frequency') ?: NULL;
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary(): array {
    $configuration = $this->getConfiguration();
    $settings = $configuration['settings'];
    $items = [];
    if ($this->configuration['payment_frequency']) {
      $items[] = $this->getPaymentFrequencySummary($this->configuration['payment_frequency']);
    }
    if (!empty($settings['confirmation_title'])) {
      $items[] = $this->t('<strong>Confirmation title:</strong> @status', [
        '@status' => $settings['confirmation_title'],
      ]);
    }
    if (!empty($settings['confirmation_message'])) {
      $items[] = $this->t('<strong>Confirmation message:</strong> @status', [
        '@status' => $settings['confirmation_message'],
      ]);
    }
    if (!empty($settings['fallback_plugin'])) {
      $items[] = $this->t('<strong>Fallback method:</strong> [@plugin_id]', [
        '@plugin_id' => $settings['fallback_plugin'],
      ]);
    }
    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

}
