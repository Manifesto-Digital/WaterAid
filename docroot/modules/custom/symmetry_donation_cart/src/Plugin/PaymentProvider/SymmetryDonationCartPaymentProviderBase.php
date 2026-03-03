<?php

namespace Drupal\symmetry_donation_cart\Plugin\PaymentProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\wateraid_donation_forms\PaymentProviderBase;

/**
 * Symmetry Donation Cart Payment ProviderBase.
 *
 * @package Drupal\symmetry_donation_cart\Plugin\PaymentProvider
 */
abstract class SymmetryDonationCartPaymentProviderBase extends PaymentProviderBase {

  /**
   * {@inheritdoc}
   */
  public function processWebformComposite(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    /** @var \Drupal\webform\WebformInterface $webform */
    if ($form_state->getFormObject()->getEntity()) {
      $webform = $form_state->getFormObject()->getEntity()->getWebform();
      if ($webform->getThirdPartyProviders() && $webform->getThirdPartySettings('symmetry_donation_cart') && ($campaign_code = $webform->getThirdPartySetting('symmetry_donation_cart', 'symmetry_campaign_code'))) {
        $element['#attached']['drupalSettings']['symmetryCampaignCode'] = $campaign_code;
      }
    }

    $element['#attached']['library'][] = 'symmetry_donation_cart/symmetry_donation_cart.library';
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId($result): string {
    return $result->id;
  }

}
