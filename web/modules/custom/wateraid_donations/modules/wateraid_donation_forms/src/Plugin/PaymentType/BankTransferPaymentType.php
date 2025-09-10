<?php

namespace Drupal\wateraid_donation_forms\Plugin\PaymentType;

use Drupal\wateraid_donation_forms\PaymentTypeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Bank Transfer PaymentType.
 *
 * @PaymentType(
 *   id = "bank_transfer",
 *   label = @Translation("Bank transfer"),
 *   ui_label = @Translation("Bank transfer"),
 *   description = @Translation("Bank transfer payment type"),
 *   weight = 0,
 *   prefix = "donation",
 * )
 *
 * @package Drupal\wateraid_donation_forms\Plugin\PaymentProvider
 */
class BankTransferPaymentType extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getDataColumns(): array {
    return [
      'payment_method',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmationPageMarkup(WebformSubmissionInterface $webform_submission): string {
    return $this->t('Payment via manual bank transfer');
  }

}
