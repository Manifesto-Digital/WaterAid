<?php

namespace Drupal\wateraid_forms\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a webform element for an address element.
 *
 * @FormElement("webform_address_sweden")
 */
class WebformAddressSweden extends WebformCompositeBase {

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
    $elements['address'] = [
      '#type' => 'textfield',
      '#title' => t('Address'),
    ];
    $elements['address_2'] = [
      '#type' => 'textfield',
      '#title' => t('Address 2'),
    ];
    $elements['postal_code'] = [
      '#type' => 'textfield',
      '#title' => t('Post Code'),
    ];
    $elements['city'] = [
      '#type' => 'textfield',
      '#title' => t('City/Town'),
    ];
    $elements['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => 'country_names',
    ];
    return $elements;
  }

}
