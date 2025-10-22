<?php

namespace Drupal\wateraid_donation_forms\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;

/**
 * Class DonationsWebformButtons.
 *
 * See Drupal issue 2858246. iCheck, which is by default applied on checkboxes /
 * radios, is deprecated and we don't want to build our custom code on top of
 * that library. We therefore extended the default radio plugins and strip
 * their dependency on iCheck as there doesn't seem to be an easy way to remove
 * this on a per element instance basis.
 * We also don't want to build these buttons on top of the Webform buttons
 * element, as they lose context on the grouping of the elements. It does not
 * work well together with the CheckboxRadioJS as the structure doesn't provide
 * enough context on the elements to wrap with CSS.
 *
 * @FormElement("donations_webform_buttons")
 *
 * @package Drupal\wateraid_donation_forms\Element
 *
 * @see https://www.drupal.org/project/webform/issues/2858246
 */
class DonationsWebformButtons extends Radios {

  /**
   * Expands a radios element into individual radio elements.
   *
   * @param mixed[] $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed[] $complete_form
   *   The com,plete form.
   *
   * @return mixed[]
   *   The updated element.
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form): array {
    $element = parent::processRadios($element, $form_state, $complete_form);

    $element['#attributes']['class'][] = 'js-donation-webform-buttons';
    // This class is used to apply styling on.
    $element['#attributes']['class'][] = 'donation-webform-buttons';

    return $element;
  }

}
