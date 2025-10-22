<?php

namespace Drupal\wateraid_donation_forms\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Number;

/**
 * Provides a textfield element with input mask and min.
 *
 * @FormElement("donations_webform_amount_textfield")
 */
class DonationsWebformAmountTextfield extends Number {

  /**
   * The text field type.
   */
  protected static string $type = 'number';

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $info = parent::getInfo();
    $class = get_class($this);
    $info['#process'][] = [$class, 'processAmount'];
    return $info;
  }

  /**
   * Processes an 'other' element.
   *
   * @param mixed[] $element
   *   The element form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed[] $complete_form
   *   The complete form.
   *
   * @return mixed[]
   *   The updated element.
   *
   * @see \Drupal\Core\Render\Element\Select
   */
  public static function processAmount(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    if (!empty($element['#input_mask'])) {
      // See if the element mask is JSON by looking for 'name':, else assume it
      // is a mask pattern.
      $input_mask = $element['#input_mask'];
      if (preg_match("/^'[^']+'\s*:/", $input_mask)) {
        $element['#attributes']['data-inputmask'] = $input_mask;
      }
      else {
        $element['#attributes']['data-inputmask-mask'] = $input_mask;
      }

      // Assumes that this input mask is available.
      $element['#attributes']['class'][] = 'js-webform-input-mask';
      $element['#attached']['library'][] = 'webform/webform.element.inputmask';
    }

    // Apply step based on the configured currency.
    $step = (string) $complete_form['#attached']['drupalSettings']['wateraidDonationForms']['currency_step'] ?? '0.01';
    $element['#step'] = $step;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderNumber($element): array {
    $element = parent::preRenderNumber($element);

    // Let min through.
    Element::setAttributes($element, ['min']);

    return $element;
  }

}
