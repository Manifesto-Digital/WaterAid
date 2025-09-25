<?php

namespace Drupal\wateraid_donation_forms\Plugin\PaymentType;

use Drupal\wateraid_donation_forms\DonationConstants;
use Drupal\wateraid_donation_forms\PaymentTypeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Card PaymentType.
 *
 * @PaymentType(
 *   id = "card",
 *   label = @Translation("Card"),
 *   ui_label = @Translation("Card"),
 *   description = @Translation("Credit/debit card payment type"),
 *   weight = 0,
 *   prefix = "donation",
 * )
 *
 * @package Drupal\wateraid_donation_forms\Plugin\PaymentProvider
 */
class CardPaymentType extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getDataColumns(): array {
    return [
      'transaction_id',
      'customer_id',
      'payment_method',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmationPageMarkup(WebformSubmissionInterface $webform_submission): string {

    $prefix = DonationConstants::DONATION_PREFIX;
    $data = $webform_submission->getData();

    if ($data[$prefix . 'payment_type'] === 'card') {
      $transaction_id = $prefix . 'transaction_id';
      if (isset($data[$transaction_id])) {
        return '<p><strong>' . $this->t('Reference number') . '</strong><br />
        <span class="wa-blue">' . $data[$transaction_id] . '</span></p>';
      }
    }
    return '';
  }

}
