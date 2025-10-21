<?php

namespace Drupal\wateraid_forms\Plugin\WebformElement;

use Drupal\wateraid_forms\Element\WebformAddressSweden as WebformAddressSwedenElement;
use Drupal\webform\Plugin\WebformElement\WebformAddress;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_capture_plus\WebformAddressCapturePlusTrait;

/**
 * Provides an 'address' element.
 *
 * @WebformElement(
 *   id = "webform_address_sweden",
 *   label = @Translation("Address (Sweden)"),
 *   description = @Translation("Provides a form element to collect address information (street, city, state, zip)."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformAddressSweden extends WebformAddress {

  use WebformAddressCapturePlusTrait;

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements(): array {
    return WebformAddressSwedenElement::getCompositeElements([]);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {
    $value = $this->getValue($element, $webform_submission, $options);
    $lines = [];
    if (!empty($value['address'])) {
      $lines['address'] = $value['address'];
    }
    if (!empty($value['address_2'])) {
      $lines['address_2'] = $value['address_2'];
    }
    if (!empty($value['postal_code'])) {
      $lines['postal_code'] = $value['postal_code'];
    }
    if (!empty($value['city'])) {
      $lines['city'] = $value['city'];
    }
    if (!empty($value['country'])) {
      $lines['country'] = $value['country'];
    }
    return $lines;
  }

}
