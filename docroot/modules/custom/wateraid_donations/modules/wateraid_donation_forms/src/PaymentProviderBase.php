<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\Core\Plugin\PluginBase;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class PaymentProviderBase.
 *
 * @package Drupal\wateraid_donation_forms
 */
abstract class PaymentProviderBase extends PluginBase implements PaymentProviderInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
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

  /**
   * {@inheritdoc}
   */
  public function getExportLabel(): string {
    return $this->getPluginDefinition()['export_label'] ?? $this->getUiLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getJsView(): string {
    $plugin_definition = $this->getPluginDefinition();

    return !empty($plugin_definition['jsView']) ? $plugin_definition['jsView'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentType(): string {
    $plugin_definition = $this->getPluginDefinition();

    return !empty($plugin_definition['paymentType']) ? $plugin_definition['paymentType'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentFrequency(): string {
    $plugin_definition = $this->getPluginDefinition();

    return !empty($plugin_definition['payment_frequency']) ? $plugin_definition['payment_frequency'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentData(array $payment, $result, ?WebformInterface $webform = NULL): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUpperLimit(): string {
    $plugin_definition = $this->getPluginDefinition();

    return !empty($plugin_definition['paymentUpperLimit']) ? $plugin_definition['paymentUpperLimit'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiresCustomerFields(): mixed {
    $plugin_definition = $this->getPluginDefinition();

    return !empty($plugin_definition['requiresCustomerFields']) ? $plugin_definition['requiresCustomerFields'] : TRUE;
  }

}
