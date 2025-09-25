<?php

namespace Drupal\wateraid_donation_google_pay\Plugin\PaymentProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\wateraid_donation_stripe\Plugin\PaymentProvider\StripePaymentProviderBase;
use Drupal\webform\WebformInterface;

/**
 * Google Pay Payment Provider.
 *
 * @PaymentProvider(
 *   id = "googlepay",
 *   label = @Translation("Google Pay payment provider"),
 *   ui_label = @Translation("Google Pay"),
 *   description = @Translation("Google Pay payment provider using Stripe"),
 *   type = "all",
 *   jsView = "GooglePayView",
 *   payment_frequency = "one_off",
 *   paymentType = "card",
 *   paymentUpperLimit = 100000,
 *   requiresCustomerFields = TRUE,
 *   enableByDefault = TRUE,
 * )
 */
class GooglePayPaymentProvider extends StripePaymentProviderBase {

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

    $element['googlepay'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'google-pay-button',
        ],
      ],
    ];

    $element['googlepay_errors'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'googlepay-errors',
        ],
      ],
    ];

    $element['#attached']['library'][] = 'wateraid_donation_google_pay/stripe.googlepayjs';
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
