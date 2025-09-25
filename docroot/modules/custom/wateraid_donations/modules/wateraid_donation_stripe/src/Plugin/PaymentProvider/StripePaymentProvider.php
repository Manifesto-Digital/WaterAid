<?php

namespace Drupal\wateraid_donation_stripe\Plugin\PaymentProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\WebformInterface;

/**
 * A Stripe Payment Provider.
 *
 * @PaymentProvider(
 *   id = "stripe",
 *   label = @Translation("Stripe payment provider"),
 *   ui_label = @Translation("Credit/debit"),
 *   export_label = @Translation("Credit card"),
 *   description = @Translation("Stripe payment provider"),
 *   type = "all",
 *   jsView = "StripeView",
 *   payment_frequency = "one_off",
 *   paymentType = "card",
 *   paymentUpperLimit = 999999.99,
 *   requiresCustomerFields = TRUE,
 * )
 *
 * @package Drupal\wateraid_donation_stripe\Plugin\PaymentProvider
 */
class StripePaymentProvider extends StripePaymentProviderBase {

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
    $element['stripe'] = [
      '#type' => 'stripe_card',
      '#title' => $this->t('Card details'),
    ];

    $submit_button_label = $this->t('Submit');
    if (!empty($complete_form['elements']['actions']['#submit__label'])) {
      $submit_button_label = $complete_form['elements']['actions']['#submit__label'];
    }

    $element['pay_and_submit'] = [
      '#type' => 'button',
      '#value' => $submit_button_label,
      '#attributes' => [
        'class' => [
          'payment-button',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId($result): string {
    return $result['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentData(array $payment, $result, ?WebformInterface $webform = NULL): array {

    if (!$webform) {
      throw new \Exception('StripePaymentProvider requires a Webform instance.');
    }

    $data = [];

    if ($result && !empty($result['id'])) {
      // Initially set payment pi_xxx id.
      $data['payment_intent_id'] = $result['id'];

      // Get the charge id from the payment intent id.
      $data['transaction_id'] = $this->getChargeIdFromPaymentIntentId($result['id'], $webform);
    }

    return $data;
  }

  /**
   * Helper function to get the charge id from payment intent id.
   *
   * @param string $payment_intent_id
   *   The payment intent id.
   * @param \Drupal\webform\WebformInterface $webform
   *   a Webform instance.
   *
   * @return mixed
   *   The charge id.
   *
   * @throws \Stripe\Exception\ApiErrorException
   */
  protected function getChargeIdFromPaymentIntentId(string $payment_intent_id, WebformInterface $webform): mixed {
    $charge_id = NULL;
    $stripe = $this->webformStripeService->getStripeClient($webform);
    // Check if we have availability over a ch_xxx id.
    $retrieve = $stripe->paymentIntents->retrieve($payment_intent_id);
    if (is_object($retrieve->charges) && is_array($retrieve->charges->data) && !empty($retrieve->charges->data[0])) {
      $charge_id = $retrieve->charges->data[0]->id ?? NULL;
    }
    return $charge_id;
  }

}
