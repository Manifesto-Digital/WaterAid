<?php

namespace Drupal\wateraid_donation_forms_test\Plugin\PaymentProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\wateraid_donation_forms\PaymentProviderBase;
use Drupal\webform\WebformInterface;

/**
 * Test Recurring PaymentProvider.
 *
 * @PaymentProvider(
 *   id = "test_recurring",
 *   label = @Translation("Test recurring payment provider"),
 *   ui_label = @Translation("Test subscription"),
 *   description = @Translation("Test payment provider - for testing..."),
 *   payment_frequency = "recurring",
 *   requiresCustomerFields = TRUE,
 * )
 *
 * @package Drupal\wateraid_donation_forms_test\Plugin\PaymentProvider
 */
class TestRecurringPaymentProvider extends PaymentProviderBase {

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
    $element['pay1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pay 1'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processPayment(array $payment, ?WebformInterface $webform = NULL): mixed {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId($result): string {
    return FALSE;
  }

}
