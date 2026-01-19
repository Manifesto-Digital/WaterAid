<?php

namespace Drupal\wateraid_donation_sf3ds\Plugin\PaymentProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\wateraid_donation_forms\PaymentProviderBase;
use Drupal\webform\WebformInterface;

/**
 * Provides the GmoBankTransferPaymentProvider class.
 *
 * @PaymentProvider(
 *   id = "sf3ds",
 *   label = @Translation("SalesForce 3DS card"),
 *   ui_label = @Translation("SalesForce 3DS card"),
 *   export_label = @Translation("SalesForce 3DS card"),
 *   description = @Translation("SalesForce 3DS card"),
 *   type = "all",
 *   payment_frequency = "one_off",
 *   paymentType = "card",
 *   paymentUpperLimit = 100000000,
 *   requiresCustomerFields = FALSE,
 * )
 *
 * @package Drupal\wateraid_donation_sf3ds\Plugin\PaymentProvider
 */
class Sf3dsPaymentProvider extends PaymentProviderBase {

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
    $element['prompt'] = [
      '#markup' => $this->t('<p>Please enter you credit card details on the following page.</p>'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId($result): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function processPayment(array $payment, ?WebformInterface $webform = NULL): mixed {
    return TRUE;
  }

}
