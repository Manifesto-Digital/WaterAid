<?php

namespace Drupal\wateraid_donation_gmo\Plugin\PaymentProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\WebformInterface;

/**
 * GMO Subscription Payment Provider.
 *
 * @PaymentProvider(
 *   id = "gmo_subscription",
 *   label = @Translation("GMO subscription payment provider"),
 *   ui_label = @Translation("Credit/debit"),
 *   export_label = @Translation("Credit card"),
 *   description = @Translation("GMO subscription payment provider"),
 *   type = "all",
 *   jsView = "GmoView",
 *   payment_frequency = "recurring",
 *   paymentType = "card",
 *   paymentUpperLimit = 2000000,
 *   requiresCustomerFields = FALSE,
 * )
 *
 * @package Drupal\wateraid_donation_gmo\Plugin\PaymentProvider
 */
class GmoSubscriptionPaymentProvider extends GmoPaymentProviderBase {

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
    $element['gmo'] = [
      '#type' => 'gmo_card',
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
      throw new \Exception('GmoSubscriptionPaymentProvider requires a Webform instance.');
    }

    return [];
  }

}
