<?php

namespace Drupal\just_giving;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * The Just Giving client.
 */
class JustGivingClient implements JustGivingClientInterface {

  /**
   * Just Giving client.
   */
  private \JustGivingClient $justGivingClient;

  /**
   * Root domain.
   */
  private string $rootDomain;

  /**
   * API Key.
   */
  private string $apiKey;

  /**
   * Api version.
   */
  private string $apiVersion;

  /**
   * Username.
   */
  private string $username;

  /**
   * Password.
   */
  private string $password;

  /**
   * The Just Giving config.
   */
  protected ImmutableConfig $config;

  /**
   * Logger channel.
   */
  protected LoggerChannelInterface $logger;

  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger) {
    $this->config = $config_factory->get('just_giving.justgivingconfig');
    $this->logger = $logger->get('just_giving');
  }

  /**
   * {@inheritDoc}
   */
  public function jgLoad(): mixed {
    $this->justGivingClient = $this->loadJustGivingClient();
    return $this->justGivingClient;
  }

  /**
   * {@inheritDoc}
   */
  public function setUsername(mixed $username): void {
    $this->username = $username;
  }

  /**
   * Set password.
   *
   * @param mixed $password
   *   The password parameter.
   */
  public function setPassword(mixed $password): void {
    $this->password = $password;
  }

  /**
   * Load just giving client.
   *
   * @return bool|\JustGivingClient
   *   Returns JustGivingClient or FALSE on error.
   */
  private function loadJustGivingClient(): bool|\JustGivingClient {

    $this->rootDomain = $this->config->get('environments');
    $this->apiKey = $this->config->get('api_key');
    $this->apiVersion = $this->config->get('api_version');
    if (!isset($this->username) && !isset($this->password)) {
      $this->username = NULL;
      $this->password = NULL;
    }
    if ($this->rootDomain
      && $this->apiKey
      && $this->apiVersion) {
      try {
        $this->justGivingClient = new \JustGivingClient($this->rootDomain,
          $this->apiKey,
          $this->apiVersion,
          $this->username,
          $this->password);
        return $this->justGivingClient;
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
        return FALSE;
      }
    }
    else {
      $message = "Missing Configuration: admin/config/just_giving/justgivingconfig";
      $this->logger->notice($message);
      return FALSE;
    }
  }

}
