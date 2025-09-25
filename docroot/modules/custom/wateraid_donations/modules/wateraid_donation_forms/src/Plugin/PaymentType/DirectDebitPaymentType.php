<?php

namespace Drupal\wateraid_donation_forms\Plugin\PaymentType;

use Drupal\wateraid_donation_forms\DonationConstants;
use Drupal\wateraid_donation_forms\PaymentTypeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Direct Debit PaymentType.
 *
 * @PaymentType(
 *   id = "direct_debit",
 *   label = @Translation("Direct debit"),
 *   ui_label = @Translation("Direct Debit"),
 *   description = @Translation("Direct debit payment type"),
 *   weight = 1,
 *   prefix = "dd",
 * )
 *
 * @package Drupal\wateraid_donation_forms\Plugin\PaymentProvider
 */
class DirectDebitPaymentType extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getDataColumns(): array {
    return [
      'first_payment_date',
      'frequency',
      'sort_code',
      'account_number',
      'account_name',
      'instruction_reference',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmationPageMarkup(WebformSubmissionInterface $webform_submission): string {

    $prefix = DonationConstants::DONATION_PREFIX;
    $data = $webform_submission->getData();
    $dd_payment_date = $data[$prefix . 'first_payment_date'] ?? NULL;
    $dd_reference = $data[$prefix . 'instruction_reference'] ?? NULL;
    $message_content = '';

    if ($dd_payment_date) {
      $message_content .= '<p><strong>' . $this->t('First Payment Date') . '</strong><br/>';
      $message_content .= $dd_payment_date;
      $message_content .= '</p>';
    }

    if ($dd_reference) {
      $message_content .= '<p><strong>' . $this->t('Direct Debit Reference') . '</strong><br/>';
      $message_content .= $dd_reference;
      $message_content .= '</p>';
    }

    return $message_content;
  }

}
