<?php

namespace Drupal\wateraid_donation_paypal\Plugin\PaymentProvider;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\wateraid_donation_forms\PaymentProviderBase;
use Drupal\webform\WebformInterface;

/**
 * PayPal Payment Provider.
 *
 * @PaymentProvider(
 *   id = "paypal_express",
 *   label = @Translation("PayPal Express payment provider"),
 *   ui_label = @Translation("PayPal"),
 *   description = @Translation("PayPal Express payment provider"),
 *   payment_frequency = "one_off",
 *   jsView = "PayPalView",
 *   paymentType = "card",
 *   paymentUpperLimit = 100000,
 *   requiresCustomerFields = TRUE,
 * )
 *
 * @package Drupal\wateraid_donation_stripe\Plugin\PaymentProvider
 */
class PayPalPaymentProvider extends PaymentProviderBase {

  /**
   * {@inheritdoc}
   */
  public function getElement(): ?FormElement {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function processWebformComposite(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    $element['paypal_express'] = [
      '#type' => 'paypal_express',
      '#title' => $this->t('PayPal'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processPayment(array $payment, ?WebformInterface $webform = NULL): mixed {
    if (!empty($payment['payment_result'])) {
      return Json::decode($payment['payment_result']);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId($result): string {
    if (is_array($result) && !empty($result['transactions'][0]['related_resources'][0]['sale']['id'])) {
      return $result['transactions'][0]['related_resources'][0]['sale']['id'];
    }
    if (!empty($result['id'])) {
      return $result['id'];
    }
    \Drupal::logger('paypal_api')->error('No transation id found for payment response: @result', [
      '@result' => Json::encode($result),
    ]);
    return 'not found';
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
