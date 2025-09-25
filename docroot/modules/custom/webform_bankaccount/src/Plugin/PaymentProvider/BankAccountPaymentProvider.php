<?php

namespace Drupal\webform_bankaccount\Plugin\PaymentProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\wateraid_donation_forms\PaymentProviderBase;
use Drupal\webform\WebformInterface;

/**
 * Bank Account Payment Provider.
 *
 * @PaymentProvider(
 *   id = "bank_account",
 *   label = @Translation("Bank account/Direct debit"),
 *   ui_label = @Translation("Direct Debit"),
 *   description = @Translation("Bank account/direct debit payment"),
 *   payment_frequency = "recurring",
 *   jsView = "DDView",
 *   paymentType = "direct_debit",
 *   paymentUpperLimit = 100000,
 *   requiresCustomerFields = TRUE,
 * )
 *
 * @package Drupal\wateraid_bankaccount\Plugin\PaymentProvider
 */
class BankAccountPaymentProvider extends PaymentProviderBase {

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
    $element['bank_account'] = [
      '#type' => 'webform_bankaccount',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processPayment(array $payment, ?WebformInterface $webform = NULL): mixed {
    $bankValidator = \Drupal::service('webform_bankaccount.validator');
    return $bankValidator->validateBankAccount($payment);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId($result): string {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentData(array $payment, $result, ?WebformInterface $webform = NULL): array {
    $data = [];

    $bank_details = $payment['payment_details']['bank_account'];

    $data['sort_code'] = $bank_details['sort_code'];
    $data['account_number'] = $bank_details['account'];
    $data['account_name'] = $bank_details['account_name'];
    $data['instruction_reference'] = $this->getInstructionReference();
    $data['first_payment_date'] = $bank_details['start_date'];

    return $data;
  }

  /**
   * Get an instruction reference.
   *
   * @return string
   *   The instruction reference.
   */
  protected function getInstructionReference(): string {
    // Reset the state cache, in case the reference count has just been updated.
    \Drupal::state()->resetCache();
    $count = \Drupal::state()->get('instruction_reference_count');

    if ($count === NULL) {
      $count = 1000001;
    }

    \Drupal::state()->set('instruction_reference_count', $count + 1);

    return 'WATD' . $count;
  }

}
