<?php

namespace Drupal\wateraid_donation_gmo\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides a webform element for a GMO single card element.
 *
 * @FormElement("gmo_card")
 */
class GmoCard extends FormElement implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['preRenderGmoCard'];
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderGmoCard'],
      ],
      '#input' => TRUE,
      '#theme' => 'input__hidden',
    ];
  }

  /**
   * Prepares a #type 'gmo_card' render element.
   *
   * @param mixed[] $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #min, #max, #attributes,
   *   #step.
   *
   * @return mixed[]
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderGmoCard(array $element): array {
    $element = [];
    /** @var \Drupal\wateraid_donation_gmo\WateraidWebformGmoService $service */
    $service = \Drupal::service('wateraid_webform_gmo');
    $library = $service->getGmoLibraryName();
    $element['#attached']['library'][] = $library;
    return $element;
  }

}
