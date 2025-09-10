<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * PaymentFrequency Interface.
 *
 * @package Drupal\wateraid_donation_forms
 */
interface PaymentFrequencyInterface extends ContainerFactoryPluginInterface {

  /**
   * Return the UI label.
   *
   * @return string
   *   The label.
   */
  public function getUiLabel(): string;

}
