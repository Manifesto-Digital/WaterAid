<?php

namespace Drupal\wateraid_donation_sf3ds\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\wateraid_donation_forms\DonationConstants;
use Drupal\wateraid_donation_forms\Element\DonationsWebformAmount;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Donations Webform Handler.
 *
 * @package Drupal\wateraid_donation_sf3ds\Plugin\WebformHandler
 *
 * @WebformHandler(
 *   id = "sf3ds",
 *   label = @Translation("Sf 3ds"),
 *   category = @Translation("SF 3ds"),
 *   description = @Translation("SF 3ds"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class Sf3dsWebformHandler extends WebformHandlerBase {

  /**
   * Webform handler for Sf3ds payments.
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

    $amount = $form_state->get(DonationsWebformAmount::STORAGE_AMOUNT) ?? $this->request->get('val');
    $payment_frequency = $form_state->get(DonationsWebformAmount::STORAGE_FREQUENCY) ?? $this->request->get('fq');
    $currency = $this->getCurrency();

    $prefix = DonationConstants::DONATION_PREFIX;

    // Extract generic payment data values.
    $payment_data = [];
    $payment_data[$prefix . 'amount'] = $amount;
    $payment_data[$prefix . 'currency'] = $currency;
    $payment_data[$prefix . 'frequency'] = $payment_frequency;
    $webform_submission->setData(array_merge($webform_submission->getData(), $payment_data));
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission): void {

    // Confirm form for sf3ds is non-webform form POSTing direct to salesforce.
    if ('sf3ds' == trim($webform_submission->getData()['payment']['payment_methods'] ?? '')) {
      $form_state->setRedirect(
        'wateraid_donation_sf3ds.cardForm',
        ['token' => $webform_submission->getToken()]
      );

      // Otherwise revert to default confirmation behavior.
    }
    else {
      parent::confirmForm($form, $form_state, $webform_submission);
    }
  }

  /**
   * Get the currency that applies to this webform.
   *
   * @return string
   *   3 character currency code.
   */
  private function getCurrency(): string {
    return $this->getWebform()->getThirdPartySetting('wateraid_donation_forms', 'currency', 'GBP');
  }

}
