<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class PaymentTypeBase.
 *
 * @package Drupal\wateraid_donation_forms
 */
abstract class PaymentTypeBase extends PluginBase implements PaymentTypeInterface {

  /**
   * Creates the Base Payment Type.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Symfony Container.
   * @param mixed[] $configuration
   *   Plugin config.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, string $plugin_id, mixed $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Returns the UI Label.
   *
   * @return mixed
   *   The UI label.
   */
  public function getUiLabel(): mixed {
    return $this->getPluginDefinition()['ui_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefix(): string {
    return $this->getPluginDefinition()['prefix'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDataColumns(): array {
    return [];
  }

}
