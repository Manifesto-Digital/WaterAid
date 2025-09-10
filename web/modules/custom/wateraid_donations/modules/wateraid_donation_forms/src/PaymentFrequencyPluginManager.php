<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\wateraid_donation_forms\Annotation\PaymentFrequency;

/**
 * A plugin manager for donation forms payment frequency plugins.
 *
 * The plugin manager is declared as a service in
 * wateraid_donation_forms.services.yml.
 */
class PaymentFrequencyPluginManager extends DefaultPluginManager {

  /**
   * Creates the discovery object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $subdir = 'Plugin/PaymentFrequency';

    $plugin_interface = PaymentFrequencyInterface::class;

    // The name of the annotation class that contains the plugin definition.
    $plugin_definition_annotation_name = PaymentFrequency::class;

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    $this->alterInfo('payment_frequency_info');
    $this->setCacheBackend($cache_backend, 'payment_frequency_info');
  }

  /**
   * {@inheritdoc}
   */
  public function findDefinitions(): array {
    $definitions = parent::findDefinitions();
    uasort($definitions, [SortArray::class, 'sortByWeightElement']);
    return $definitions;
  }

}
