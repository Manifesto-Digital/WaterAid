<?php

namespace Drupal\wateraid_donation_paypal\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides a webform element for PayPal Express payment.
 *
 * @FormElement("paypal_express")
 */
class PayPalExpress extends FormElement implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['preRenderPayPalExpress'];
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderPayPalExpress'],
      ],
      '#input' => TRUE,
      '#theme' => 'input__hidden',
    ];
  }

  /**
   * Prepares a #type 'paypal_express' render element.
   *
   * @param mixed[] $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #min, #max, #attributes,
   *   #step.
   *
   * @return mixed[]
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderPayPalExpress(array $element): array {

    $element['#attributes']['type'] = 'hidden';
    $element['#attributes']['value'] = '';
    $element['#attributes']['id'] = 'paypal-express-checkout';
    Element::setAttributes($element, ['name', 'value']);

    $element_html_id = Html::getUniqueId('paypal-express');
    $build['paypal_express_element'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' =>
          [
            'paypal-express-container',
          ],
        'id' => $element_html_id,
      ],
    ];

    $build['stripe_card_errors'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['paypal-express-errors']],
    ];

    $element['#children'] = $build;

    $element['#attached']['library'][] = 'wateraid_donation_paypal/paypal.checkoutjs';

    /** @var \Drupal\wateraid_donation_paypal\PayPalApiService $paypal_api */
    $paypal_api = \Drupal::service('wateraid_donation_paypal.paypal_api');
    $mode = $paypal_api->getMode() == 'live' ? 'production' : 'sandbox';
    $client = [$mode => $paypal_api->getPubKey()];
    $element['#attached']['drupalSettings']['webformPayPalExpressElements'] = [
      'client' => $client,
      'mode' => $mode,
    ];

    // Create an empty style object that can be overridden by the theme.
    $element['#attached']['drupalSettings']['webformPayPalExpressElements']['style'] = [];

    return $element;
  }

}
