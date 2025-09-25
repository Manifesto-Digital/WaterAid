<?php

namespace Drupal\wateraid_donation_stripe\Plugin\PaymentProvider;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\wateraid_donation_forms\Exception\PaymentException;
use Drupal\webform\WebformInterface;

/**
 * Apple PayPayment Provider.
 *
 * @PaymentProvider(
 *   id = "applepay",
 *   label = @Translation("Apple Pay payment provider"),
 *   ui_label = @Translation("Apple Pay"),
 *   description = @Translation("Apple Pay payment provider using Stripe"),
 *   type = "all",
 *   jsView = "ApplePayView",
 *   payment_frequency = "one_off",
 *   paymentType = "card",
 *   paymentUpperLimit = 100000,
 *   requiresCustomerFields = TRUE,
 * )
 *
 * @package Drupal\wateraid_donation_stripe\Plugin\PaymentProvider
 */
class ApplePayPaymentProvider extends StripePaymentProviderBase {

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
    parent::processWebformComposite($element, $form_state, $complete_form);

    $element['applepay'] = [
      '#type' => 'button',
      '#value' => '',
      '#attributes' => [
        'class' => [
          'apple-pay-button',
        ],
      ],
    ];

    $element['applepay_errors'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'applepay-errors',
        ],
      ],
    ];

    $element['#attached']['library'][] = 'wateraid_donation_stripe/stripe.applepayjs';

    $apple_pay_server_side_payment_processing = \Drupal::config('apple_pay_server_side_payment_processing');
    if ($apple_pay_server_side_payment_processing === NULL) {
      $apple_pay_server_side_payment_processing = FALSE;
    }

    $element['#attached']['drupalSettings']['wateraidApplePay']['serverSidePayment'] = $apple_pay_server_side_payment_processing;
  }

  /**
   * {@inheritdoc}
   */
  public function processPayment(array $payment, ?WebformInterface $webform = NULL): mixed {

    if (!$webform) {
      throw new \Exception('StripePaymentProvider requires a Webform instance.');
    }

    if (!empty($payment['payment_token'])) {

      $params = [
        'amount' => $payment['amount'] * 100,
        'description' => $payment['description'],
        'source' => $payment['payment_token'],
        'currency' => $payment['currency'],
        'metadata' => $payment['customer_details'],
      ];

      $stripe = $this->webformStripeService->getStripeClient($webform);

      try {
        return $stripe->charges->create($params);
      }
      catch (\Exception $e) {
        $this->logger->error('Error: @error <br /> @args', [
          '@args' => Json::encode([
            'object' => 'Charge',
            'method' => 'create',
            'params' => $params,
          ]),
          '@error' => $e->getMessage(),
        ]);

        // Cast stripe error to either payment failure or system error.
        $code = $e->getHttpStatus() == 402 ? PaymentException::PAYMENT_FAILURE : PaymentException::SYSTEM_ERROR;
        throw new PaymentException($e->getMessage(), $code, $e);
      }
    }
    else {
      return (object) ['id' => $payment['payment_result']];
    }
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

    if ($result && !empty($result->id)) {
      $data['transaction_id'] = $result->id;
    }

    return $data;
  }

}
