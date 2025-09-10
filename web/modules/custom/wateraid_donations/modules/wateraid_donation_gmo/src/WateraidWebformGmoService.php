<?php

namespace Drupal\wateraid_donation_gmo;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Provides GMO payment services.
 */
class WateraidWebformGmoService {

  /**
   * The GMO integration seettings config.
   */
  protected ImmutableConfig $config;

  /**
   * Constructs a WateraidWebformGmoService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('wateraid_donation_gmo.settings');
  }

  /**
   * Get the integration mode settings form config.
   *
   * @return string
   *   The integration mode (live or test).
   */
  private function getMode(): string {
    $mode = $this->config->get('mode');
    if (empty($mode)) {
      return 'test';
    }
    else {
      return (string) $mode;
    }
  }

  /**
   * Retrieves config for the current mode context.
   *
   * @param string $config_key
   *   The config key, omitting mode-specific suffixes.
   *
   * @return string
   *   The config value as a string.
   */
  private function getConfigViaModeSuffix(string $config_key): string {
    $mode = $this->getMode();
    $key = $config_key . '_' . $mode;
    return (string) $this->config->get($key) ?? '';
  }

  /**
   * Retrieve the shop ID based on the current environment/mode.
   *
   * @return string
   *   The Shop ID.
   */
  public function getShopId(): string {
    return $this->getConfigViaModeSuffix('shop_id');
  }

  /**
   * Get the GMO payment library based on the current environment/mode.
   *
   * @return string
   *   The GMO payment library name.
   */
  public function getGmoLibraryName(): string {
    $mode = $this->getMode();
    return 'wateraid_donation_gmo/gmo.gmojs-' . $mode;
  }

  /**
   * Retrieve the SalesForce URL based on the current environment/mode.
   *
   * @return string
   *   The SalesForce URL.
   */
  public function getSalesforceUrl(): string {
    return $this->getConfigViaModeSuffix('salesforce_url');
  }

  /**
   * Retrieve the SalesForce token based on the current environment/mode.
   *
   * @return string
   *   The SalesForce token.
   */
  public function getSalesforceToken(): string {
    return $this->getConfigViaModeSuffix('salesforce_token');
  }

}
