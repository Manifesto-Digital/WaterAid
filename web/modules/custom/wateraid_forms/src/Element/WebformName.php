<?php

namespace Drupal\wateraid_forms\Element;

use Drupal\Component\Utility\Html;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a webform element for an name element.
 *
 * @FormElement("wateraid_forms_webform_name")
 */
class WebformName extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $info = parent::getInfo();
    unset($info['#theme']);
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {

    $elements = [];

    $elements['title'] = [
      '#type' => 'select',
      '#title' => t('Title'),
      '#options' => 'titles',
    ];

    $elements['first'] = [
      '#type' => 'textfield',
      '#title' => t('First name'),
      '#prefix' => '<div class="' . HTML::cleanCssIdentifier('wa-subelement-wrapper-name') . '">',
    ];

    $elements['last'] = [
      '#type' => 'textfield',
      '#title' => t('Last name'),
      '#suffix' => '</div>',
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderWebformCompositeFormElement($element) {
    $element = parent::preRenderWebformCompositeFormElement($element);
    // Load the javascript.
    $element['#attached']['library'][] = 'wateraid_forms/wateraid_forms';

    return $element;
  }

}
