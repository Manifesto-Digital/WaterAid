<?php

namespace Drupal\webform_capture_plus\Plugin\WebformElement;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\Plugin\WebformElement\WebformAddress;
use Drupal\webform_capture_plus\WebformAddressCapturePlusTrait;

/**
 * Provides a 'capture plus address' element.
 *
 * @WebformElement(
 *   id = "webform_address_capture_plus",
 *   label = @Translation("Address (Capture Plus)"),
 *   description = @Translation("Provides a form element to collect address information (street, city, state, zip)."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformAddressCapturePlus extends WebformAddress {

  use WebformAddressCapturePlusTrait;

  /**
   * {@inheritdoc}
   */
  public function getPluginLabel() {
    $label = parent::getPluginLabel();

    $site = \Drupal::config('system.date')->get('country.default');
    if ($site == 'GB') {
      switch ($this->getPluginId()) {
        case 'webform_address_capture_plus':
          $label = new TranslatableMarkup('Address (Capture Plus) - Unsupported');
          break;

        case 'webform_address':
          $label = new TranslatableMarkup('Address - Unsupported');
          break;
      }
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDescription() {
    $description = parent::getPluginDescription();

    $site = \Drupal::config('system.date')->get('country.default');
    if ($site == 'GB') {
      switch ($this->getPluginId()) {
        case 'webform_address_capture_plus':
          $description = new TranslatableMarkup('Capture Plus is no longer supported. Please use Loqate Address instead.');
          break;

        case 'webform_address':
          $description = new TranslatableMarkup('Address is no longer supported. Please use Loqate Address instead.');
          break;
      }
    }

    return $description;
  }

}
