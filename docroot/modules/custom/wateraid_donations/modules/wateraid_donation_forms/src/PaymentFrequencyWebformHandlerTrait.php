<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Trait PaymentFrequencyWebformHandlerTrait.
 *
 * Used for Webform Handlers that need to follow a payment frequency condition.
 *
 * @package Drupal\wateraid_donation_forms
 */
trait PaymentFrequencyWebformHandlerTrait {

  /**
   * Default configuration.
   *
   * @return null[]
   *   The payment frequency setting.
   */
  public function getPaymentFrequencyDefaultConfiguration(): array {
    return [
      'payment_frequency' => NULL,
    ];
  }

  /**
   * Get a summary for the Payment Frequency.
   *
   * @param string $payment_frequency
   *   The frequency machine name.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A summary string.
   */
  public function getPaymentFrequencySummary(string $payment_frequency): TranslatableMarkup {
    return $this->t('<strong>Applies to:</strong> [@frequency]', [
      '@frequency' => $payment_frequency,
    ]);
  }

  /**
   * Helper to build a Payment Frequency form.
   *
   * @param mixed[] $form
   *   The render form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state obj.
   *
   * @return mixed[]
   *   The render form.
   */
  public function buildPaymentFrequencyForm(array $form, FormStateInterface $form_state): array {

    $options = [];

    /** @var DonationService $donation_service */
    $donation_service = \Drupal::service('wateraid_donation_forms.donation');

    /** @var PaymentFrequencyInterface $frequency */
    foreach ($donation_service->getPaymentFrequencies() as $id => $frequency) {
      $options[$id] = $frequency->getUiLabel();
    }

    $form['payment_frequency'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment frequency'),
      '#description' => $this->t('If a payment frequency is selected here, the handler will only fire for submissions using this frequency. If no frequency is selected here, it will be sent for all frequencies.'),
      '#options' => $options,
      '#empty_option' => $this->t('None'),
      '#default_value' => $this->configuration['payment_frequency'],
    ];

    return $form;
  }

  /**
   * Determines the payment frequency configured against a Webform Handler.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The Webform Submission.
   *
   * @return bool
   *   True or False.
   *
   * @see \Drupal\wateraid_donation_forms\Plugin\WebformHandler\WateraidEmailWebformHandler::getMessage()
   */
  public function paymentFrequencyMatches(WebformSubmissionInterface $webform_submission): bool {
    $data = $webform_submission->getData();
    // NULL for Payment Frequency needs to be considered a match.
    return !$this->configuration['payment_frequency']
      || $this->configuration['payment_frequency'] === $data[DonationConstants::DONATION_PREFIX . 'frequency'];
  }

}
