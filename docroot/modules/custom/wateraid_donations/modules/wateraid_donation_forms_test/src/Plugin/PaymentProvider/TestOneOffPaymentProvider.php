<?php

namespace Drupal\wateraid_donation_forms_test\Plugin\PaymentProvider;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\wateraid_donation_forms\Exception\PaymentException;
use Drupal\wateraid_donation_forms\PaymentProviderBase;
use Drupal\webform\WebformInterface;

/**
 * TestOneOff Payment Provider.
 *
 * @PaymentProvider(
 *   id = "test_one_off",
 *   label = @Translation("Test one off payment provider"),
 *   ui_label = @Translation("Test payment"),
 *   description = @Translation("Test payment provider - for testing..."),
 *   payment_frequency = "one_off",
 *   jsView = "OneOffTestView",
 *   paymentType = "card",
 *   requiresCustomerFields = TRUE,
 * )
 *
 * @package Drupal\wateraid_donation_forms_test\Plugin\PaymentProvider
 */
class TestOneOffPaymentProvider extends PaymentProviderBase {

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
    $element['server_response'] = [
      '#type' => 'radios',
      '#title' => $this->t('Test server-side response'),
      '#options' => [
        'success' => new TranslatableMarkup('Success'),
        'failure' => new TranslatableMarkup('Payment failure'),
        'error' => new TranslatableMarkup('System error'),
      ],
      '#attributes' => ['class' => ['server-response']],
    ];

    $element['client_response'] = [
      '#type' => 'radios',
      '#title' => $this->t('Test client-side response'),
      '#options' => [
        'client_success' => new TranslatableMarkup('Success'),
        'client_failure' => new TranslatableMarkup('Payment failure'),
        'client_error' => new TranslatableMarkup('System error'),
      ],
      '#attributes' => ['class' => ['client-response']],
    ];

    $element['#attached']['library'][] = 'wateraid_donation_forms_test/wateraid_donations_forms_test.js';
  }

  /**
   * {@inheritdoc}
   */
  public function processPayment(array $payment, ?WebformInterface $webform = NULL): mixed {
    $payment_details = $payment['payment_details'];

    if ($payment_details['server_response'] === 'failure') {
      throw new PaymentException('test payment failure', PaymentException::PAYMENT_FAILURE);
    }
    elseif ($payment_details['server_response'] === 'error') {
      throw new PaymentException('test payment failure', PaymentException::SYSTEM_ERROR);
    }

    if (!empty($payment['payment_result'])) {
      return Json::decode($payment['payment_result']);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId($result): string {
    if (is_array($result) && !empty($result['token'])) {
      return $result['token'];
    }
    return rand();
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentData(array $payment, $result, ?WebformInterface $webform = NULL): array {
    $data = [];

    if ($this->getTransactionId($result)) {
      $data['transaction_id'] = $this->getTransactionId($result);
    }

    return $data;
  }

}
