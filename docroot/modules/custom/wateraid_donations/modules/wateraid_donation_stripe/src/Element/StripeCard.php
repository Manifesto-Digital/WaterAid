<?php

namespace Drupal\wateraid_donation_stripe\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElementBase;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides a webform element for a Stripe single card element.
 *
 * @FormElement("stripe_card")
 */
class StripeCard extends FormElementBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['preRenderStripeCard'];
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderStripeCard'],
      ],
      '#input' => TRUE,
      '#theme' => 'input__hidden',
    ];
  }

  /**
   * Prepares a #type 'stripe_card' render elementRemo.
   *
   * @param mixed[] $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #min, #max, #attributes,
   *   #step.
   *
   * @return mixed[]
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderStripeCard(array $element): array {

    $element['#attributes']['type'] = 'hidden';
    $element['#attributes']['value'] = '';
    $element['#attributes']['id'] = Html::getUniqueId('stripe-token');
    Element::setAttributes($element, ['name', 'value']);

    $element_html_id = Html::getUniqueId('stripe-card');
    $build['stripe_card_element'] = [
      '#type' => 'container',
      '#attributes' => ['id' => $element_html_id, 'class' => ['stripe-card-element']],
    ];

    $element_errors_id = Html::getUniqueId('stripe-card-errors');
    $build['stripe_card_errors'] = [
      '#type' => 'container',
      '#attributes' => ['id' => $element_errors_id, 'class' => ['stripe-card-errors']],
    ];
    $element['#children'] = $build;

    $element['#attached']['library'][] = 'wateraid_donation_stripe/stripe.stripejs';

    $stripe_api = \Drupal::service('stripe_api.stripe_api');
    $element['#attached']['drupalSettings']['webformStripeElements']['public_key'] = $stripe_api->getPubKey();
    $element['#attached']['drupalSettings']['webformStripeElements']['elements'][$element_html_id] = [
      'type' => 'card',
      'element' => '#' . $element_html_id,
      'errorElement' => '#' . $element_errors_id,
    ];

    // Create an empty style object that can be overridden by the theme.
    $element['#attached']['drupalSettings']['webformStripeElements']['style'] = [];

    return $element;
  }

}
