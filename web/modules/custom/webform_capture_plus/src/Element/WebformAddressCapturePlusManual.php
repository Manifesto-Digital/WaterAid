<?php

namespace Drupal\webform_capture_plus\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\Element\WebformAddress;

/**
 * Provides a 'webform_address_capture_plus_manual' element.
 *
 * WebformElement assumed an Element with the same ID.
 *
 * @FormElement("webform_address_capture_plus_manual")
 */
class WebformAddressCapturePlusManual extends WebformAddress {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $composite_elements = parent::getCompositeElements($element);

    $composite_elements['paf'] = [
      '#type' => 'hidden',
      '#title' => t('PAF'),
      '#attributes' => [
        'data-paf' => '',
      ],
    ];

    return $composite_elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $info = parent::getInfo();

    $class = get_class($this);
    $info['#theme_wrappers'] = ['container'];
    $info['#process'][] = [$class, 'processAddress'];

    return $info;
  }

  /**
   * Processes the address widget to add enter address manually functionality.
   *
   * @param mixed[] $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return mixed[]
   *   The processed element.
   */
  public static function processAddress(array $element, FormStateInterface $form_state): array {
    $wrapper_id = Html::getUniqueId('pca-wrapper');

    $element['#prefix'] = '<div id="' . $wrapper_id . '" class="pca-wrapper">';
    $element['#suffix'] = '</div>';
    $element['manual'] = [
      '#type' => 'button',
      '#name' => $element['#id'] . '_manual',
      '#value' => new TranslatableMarkup('Enter address manually'),
      '#ajax' => [
        'callback' => [static::class, 'ajaxCallback'],
        'wrapper' => $wrapper_id,
        'method' => 'replace',
      ],
      '#attributes' => [
        'class' => ['capture-manual', 'link'],
      ],
      '#limit_validation_errors' => [],
    ];

    return $element;
  }

  /**
   * Handles the enter address manually button callback.
   *
   * @param mixed[] $form
   *   The current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return mixed
   *   The address element array being set to manual.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state): mixed {
    $form_parents = static::getFormParents($form_state);
    $find_element = NestedArray::getValue($form, $form_parents);
    $find_element['#attributes']['data-pcamanual'] = 'pcamanual';
    return $find_element;
  }

  /**
   * Get the form parents of the address element.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return mixed[]
   *   An array of form parents for the address element.
   */
  protected static function getFormParents(FormStateInterface $form_state): array {
    $form_parents = [];

    if ($triggering_element = $form_state->getTriggeringElement()) {
      $form_parents = $triggering_element['#array_parents'];
      array_pop($form_parents);
    }

    return $form_parents;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $value = parent::valueCallback($element, $input, $form_state);

    if (!empty($value['paf']) && ($value['paf'] == 'lookup' || $value['paf'] === TRUE)) {
      $value['paf'] = TRUE;
    }
    else {
      $value['paf'] = FALSE;
    }

    return $value;
  }

}
