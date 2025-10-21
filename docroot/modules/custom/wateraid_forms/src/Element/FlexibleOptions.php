<?php

namespace Drupal\wateraid_forms\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * Provides a webform element for flexible options.
 *
 * @FormElement("flexible_options")
 */
class FlexibleOptions extends Checkboxes {

  /**
   * An array of the current params, keyed by the vocab id.
   *
   * @param mixed[] $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   * @param mixed[] $complete_form
   *   Complete form.
   *
   * @return mixed[]
   *   The updated element.
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form): array {
    if (!empty($element['#flex_type']) && $element['#flex_type'] == 'yes_no') {

      // @see Checkboxes - code copied and adapted from there.
      $value = is_array($element['#value']) ? $element['#value'] : [];
      $element['#tree'] = TRUE;
      if (count($element['#options']) > 0) {
        if (!isset($element['#default_value']) || $element['#default_value'] == 0) {
          $element['#default_value'] = [];
        }
        $weight = 0;
        foreach ($element['#options'] as $key => $choice) {
          if ($key === 0) {
            $key = '0';
          }
          $weight += 0.001;

          $element += [$key => []];
          $element[$key] += [
            '#type' => 'radios',
            // @todo make this configurable please...
            '#options' => ['yes' => 'Yes', 'no' => 'No'],
            '#title' => $choice,
            '#return_value' => $key,
            '#default_value' => isset($value[$key]) ? 'yes' : 'no',
            '#attributes' => $element['#attributes'],
            '#ajax' => $element['#ajax'] ?? NULL,
            '#error_no_message' => TRUE,
            '#weight' => $weight,
          ];
        }
      }
    }
    else {
      $element = parent::processCheckboxes($element, $form_state, $complete_form);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      $value = [];
      $element += ['#default_value' => []];
      foreach ($element['#default_value'] as $key) {
        if (!empty($key)) {
          $value[$key] = $key;
        }
      }
      return $value;
    }
    elseif (is_array($input)) {
      // Programmatic form submissions use NULL to indicate that a checkbox
      // should be unchecked. We therefore remove all NULL elements from the
      // array before constructing the return value, to simulate the behavior
      // of web browsers (which do not send unchecked checkboxes to the server
      // at all). This will not affect non-programmatic form submissions, since
      // all values in \Drupal::request()->request are strings.
      // @see \Drupal\Core\Form\FormBuilderInterface::submitForm()
      foreach ($input as $key => $value) {
        if (!isset($value) || $value == 'no') {
          unset($input[$key]);
        }
        else {
          $input[$key] = $key;
        }
      }
      return array_combine($input, $input);
    }
    else {
      return [];
    }
  }

}
