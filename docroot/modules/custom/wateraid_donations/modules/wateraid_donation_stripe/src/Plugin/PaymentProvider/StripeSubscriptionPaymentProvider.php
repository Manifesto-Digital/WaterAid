<?php

namespace Drupal\wateraid_donation_stripe\Plugin\PaymentProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\WebformInterface;

/**
 * Stripe Subscription Payment Provider.
 *
 * @PaymentProvider(
 *   id = "stripe_subscription",
 *   label = @Translation("Stripe subscription payment provider"),
 *   ui_label = @Translation("Credit/debit"),
 *   export_label = @Translation("Credit card"),
 *   description = @Translation("Stripe subscription payment provider"),
 *   type = "all",
 *   jsView = "StripeView",
 *   payment_frequency = "recurring",
 *   paymentType = "card",
 *   paymentUpperLimit = 100000,
 *   requiresCustomerFields = TRUE,
 * )
 *
 * @package Drupal\wateraid_donation_stripe\Plugin\PaymentProvider
 */
class StripeSubscriptionPaymentProvider extends StripePaymentProviderBase {

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
    return $result->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentData(array $payment, $result, ?WebformInterface $webform = NULL): array {

    if (!$webform) {
      throw new \Exception('StripeSubscriptionPaymentProvider requires a Webform instance.');
    }

    $data = [];

    if ($result && !empty($result['id'])) {
      // Initially record the pi_xxx as id.
      $data['payment_intent_id'] = $result['id'];

      if (!empty($result['customer']) && !empty($result['subscription_id'])) {
        // Capture sub_xxx and cus_xxx if available.
        $data['transaction_id'] = $result['subscription_id'];
        $data['customer_id'] = $result['customer'];
      }
      else {
        $subscription_details = $this->getCustomerIdAndSubscriptionId($result['id'], $webform);
        $data['customer_id'] = $subscription_details->customer_id;
        $data['transaction_id'] = $subscription_details->subscription_id;
      }

    }

    return $data;
  }

  /**
   * Helper function to find out the Customer and Subscription id for SCA cards.
   *
   * @param string $payment_intent_id
   *   The payment intent id.
   * @param \Drupal\webform\WebformInterface $webform
   *   a Webform instance.
   *
   * @return object
   *   Return an array object with customer and subscription ids.
   *
   * @throws \Stripe\Exception\ApiErrorException
   */
  protected function getCustomerIdAndSubscriptionId(string $payment_intent_id, WebformInterface $webform): object {
    $stripe = $this->webformStripeService->getStripeClient($webform);
    $retrieve = $stripe->paymentIntents->retrieve($payment_intent_id);
    $customer_id = $retrieve->charges->data[0]->customer;
    $invoice_id = $retrieve->charges->data[0]->invoice;
    $invoice = $stripe->invoices->retrieve($invoice_id);
    $subscription_id = $invoice->subscription;
    return (object) [
      'customer_id' => $customer_id,
      'subscription_id' => $subscription_id,
    ];
  }

}
