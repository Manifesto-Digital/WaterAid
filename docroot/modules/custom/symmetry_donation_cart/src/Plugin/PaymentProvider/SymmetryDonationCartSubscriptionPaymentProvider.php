<?php

namespace Drupal\symmetry_donation_cart\Plugin\PaymentProvider;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\WebformInterface;

/**
 * Symmetry Donation Cart Subscription Payment Provider.
 *
 * @PaymentProvider(
 *   id = "donation_cart_subscription",
 *   label = @Translation("Symmetry Donation Cart Recurring payment provider"),
 *   ui_label = @Translation("Donation Cart Subscription"),
 *   description = @Translation("Symmetry Donation Cart Recurring payment provider"),
 *   payment_frequency = "recurring",
 *   jsView = "DonationCartView",
 *   paymentType = "card",
 *   paymentUpperLimit = 100000,
 *   requiresCustomerFields = FALSE,
 * )
 *
 * @package Drupal\symmetry_donation_cart\Plugin\PaymentProvider
 */
class SymmetryDonationCartSubscriptionPaymentProvider extends SymmetryDonationCartPaymentProviderBase {

  /**
   * {@inheritdoc}
   */
  public function getElement(): ?FormElement {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function processPayment(array $payment, ?WebformInterface $webform = NULL): mixed {
    if (!empty($payment['payment_result'])) {
      return Json::decode($payment['payment_result']);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId($result): string {
    return $result->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentData(array $payment, $result, ?WebformInterface $webform = NULL): array {
    $data = [];

    if ($transaction_id = $this->getTransactionId($result)) {
      $data['transaction_id'] = $transaction_id;
    }

    return $data;
  }

}
