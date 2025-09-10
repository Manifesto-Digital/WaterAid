<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\wateraid_donation_forms\Annotation\Fallback;

/**
 * A plugin manager for Fallback plugins.
 */
class FallbackPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Fallback', $namespaces, $module_handler, FallbackInterface::class, Fallback::class);
    $this->alterInfo('fallback_info');
    $this->setCacheBackend($cache_backend, 'fallback_info');
  }

}
