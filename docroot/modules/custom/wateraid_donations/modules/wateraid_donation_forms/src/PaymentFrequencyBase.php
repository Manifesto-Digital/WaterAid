<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class PaymentFrequencyInterface.
 *
 * @package Drupal\wateraid_donation_forms
 */
abstract class PaymentFrequencyBase extends PluginBase implements PaymentFrequencyInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getUiLabel(): string {
    return $this->getPluginDefinition()['ui_label'];
  }

}
