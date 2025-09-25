<?php

namespace Drupal\wateraid_donation_gmo\Plugin\PaymentProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\WebformInterface;

/**
 * Provides the GmoBankTransferPaymentProvider class.
 *
 * @PaymentProvider(
 *   id = "gmo_bank_transfer",
 *   label = @Translation("Bank transfer (Japan)"),
 *   ui_label = @Translation("Bank transfer"),
 *   export_label = @Translation("Bank transfer"),
 *   description = @Translation("SalesForce Bank transfer (Japan)"),
 *   type = "all",
 *   jsView = "GmoView",
 *   payment_frequency = "one_off",
 *   paymentType = "bank_transfer",
 *   paymentUpperLimit = 100000000,
 *   requiresCustomerFields = FALSE,
 * )
 *
 * @package Drupal\wateraid_donation_gmo\Plugin\PaymentProvider
 */
class GmoBankTransferPaymentProvider extends GmoPaymentProviderBase {

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

    $element['instructions'] = [
      '#markup' => $this->t('<p>Bank transfer instructions will be provided after the form is submitted.</p>'),
    ];

    $element['gmo'] = [
      '#type' => 'gmo_card',
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
      throw new \Exception('GmoBankTransferPaymentProvider requires a Webform instance.');
    }

    return [];
  }

}
