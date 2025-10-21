<?php

namespace Drupal\wateraid_donation_stripe\Plugin\PaymentProvider;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\wateraid_donation_forms\Exception\PaymentException;
use Drupal\webform\WebformInterface;

/**
 * Stripe FixedPeriod Payment Provider.
 *
 * @PaymentProvider(
 *   id = "stripe_fixed_period",
 *   label = @Translation("Stripe fixed period payment provider"),
 *   ui_label = @Translation("Credit/debit"),
 *   export_label = @Translation("Credit card"),
 *   description = @Translation("Stripe fixed period payment provider"),
 *   type = "all",
 *   jsView = "StripeView",
 *   payment_frequency = "fixed_period",
 *   paymentType = "card",
 *   paymentUpperLimit = 100000,
 *   requiresCustomerFields = TRUE,
 * )
 *
 * @package Drupal\wateraid_donation_stripe\Plugin\PaymentProvider
 */
class StripeFixedPeriodPaymentProvider extends StripePaymentProviderBase {

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
  public function processPayment(array $payment, ?WebformInterface $webform = NULL): mixed {
    // Fail if no payment response details.
    if (!($payment['payment_response']['subscriptionSchedule'] ?? NULL)) {
      throw new PaymentException('Schedule not created');
    }
    // Fail on any error.
    if (!empty($payment['payment_response']['error'])) {
      $this->logger->error('Error: @error <br /> @args', [
        '@args' => Json::encode([
          'object' => 'subscriptionSchedule',
          'method' => 'create',
          'type' => $payment['payment_response']['error']['type'],
          'code' => $payment['payment_response']['error']['code'],
          'doc_url' => $payment['payment_response']['error']['doc_url'],
          'params' => $payment['payment_response']['error']['payment_intent'],
        ]),
        '@error' => $payment['payment_response']['error']['message'],
      ]);
      throw new PaymentException('Error received from Schedule creation');
    }
    return $payment['payment_response']['subscriptionSchedule'];
  }

}
