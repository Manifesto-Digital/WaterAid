<?php

namespace Drupal\wateraid_donation_paypal;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\webform\WebformInterface;

/**
 * PayPal Api Service.
 *
 * @package Drupal\wateraid_donation_paypal
 */
class PayPalApiService {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   */
  protected ConfigFactory $configFactory;

  /**
   * Paypal configuration settings.
   */
  protected ImmutableConfig $config;

  /**
   * Entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Logger.
   */
  protected LoggerChannelInterface $logger;

  /**
   * Key repository.
   */
  protected KeyRepositoryInterface $key;

  /**
   * PayPalApiService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger.
   * @param \Drupal\key\KeyRepositoryInterface $key
   *   Key.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger, KeyRepositoryInterface $key) {
    $this->config = $config_factory->get('wateraid_donation_paypal.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->key = $key;
  }

  /**
   * Get mode - test or live.
   *
   * @return string
   *   PayPal connection mode.
   */
  public function getMode(): string {
    $mode = $this->config->get('mode');

    // Setting mode to live.
    if (!$mode) {
      $mode = 'live';
    }

    return $mode;
  }

  /**
   * Get API secret key for current mode.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A Webform instance.
   *
   * @return string
   *   API key.
   */
  public function getApiKey(?WebformInterface $webform = NULL): string {
    $config_key = $this->getMode() . '_secret_key';

    // @see wateraid_donation_paypal_webform_third_party_settings_form_alter().
    if (!$key_id = $webform?->getThirdPartySetting('wateraid_donation_paypal', $config_key)) {
      $key_id = $this->config->get($config_key);
    }

    if (empty($key_id)) {
      return '';
    }
    return $this->key->getKey($key_id)->getKeyValue();
  }

  /**
   * Get public key for current mode.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *  A Webform instance.
   *
   * @return string
   *   Public key.
   */
  public function getPubKey(?WebformInterface $webform = NULL): string {
    $config_key = $this->getMode() . '_public_key';

    // @see wateraid_donation_paypal_webform_third_party_settings_form_alter().
    if (!$key_id = $webform?->getThirdPartySetting('wateraid_donation_paypal', $config_key)) {
      $key_id = $this->config->get($config_key);
    }

    if (empty($key_id)) {
      return '';
    }

    return $this->key->getKey($key_id)->getKeyValue();
  }

}
