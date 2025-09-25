<?php

namespace Drupal\wateraid_donation_forms\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform_jqueryui_buttons\Element\WebformButtonsOther;

/**
 * Provides a webform element for buttons with another option.
 *
 * @FormElement("donations_amount_webform_buttons_other")
 */
class DonationsAmountWebformButtonsOther extends WebformButtonsOther {

  /**
   * {@inheritdoc}
   */
  public static function processWebformOther(&$element, FormStateInterface $form_state, &$complete_form): array {
    // Making sure it is only getting resetting on the first time.
    if (empty($form_state->getUserInput())) {
      // Webform upgrade setting default value for other element.
      unset($element["buttons"]["#default_value"], $element["other"]["#default_value"]);
    }

    $element = parent::processWebformOther($element, $form_state, $complete_form);

    // Check if the form is using v2.
    $webform_id = $complete_form['#webform_id'];

    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($webform_id);

    // Get the webform style version.
    $style_version = $webform->getThirdPartySetting('wateraid_forms', 'style_version', 'v2');

    if ($style_version == 'v2') {
      // Replace the Webform other element library for v2 forms.
      if (($key = array_search('webform/webform.element.other', $element['#attached']['library'])) !== FALSE) {
        unset($element['#attached']['library'][$key]);
      }
      $element['#attached']['library'][] = 'wateraid_donation_forms/wateraid_donation_forms.element.other.v2';
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateWebformOther(&$element, FormStateInterface $form_state, &$complete_form): void {
    $frequency_parents = [$element['#parents'][0], 'frequency'];
    $frequency = NestedArray::getValue($form_state->getValues(), $frequency_parents);

    // Only provide validation if it is for the currently selected frequency.
    if ($element['#parents'][2] === $frequency) {
      parent::validateWebformOther($element, $form_state, $complete_form);
    }
  }

}
