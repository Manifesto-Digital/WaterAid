<?php

declare(strict_types=1);

namespace Drupal\wateraid_donation_stripe;

use Drupal\key\KeyRepositoryInterface;
use Drupal\stripe_api\StripeApiService;
use Drupal\webform\WebformInterface;
use Psr\Log\LoggerInterface;
use Stripe\StripeClient;

/**
 * A Stripe payment service.
 *
 * @package Drupal\wateraid_donation_stripe
 */
class WaterAidWebformStripeService {

  /**
   * A logger instance.
   */
  protected LoggerInterface $logger;

  /**
   * Stripe API service.
   */
  protected StripeApiService $stripeApi;

  /**
   * The Key Repository.
   */
  protected KeyRepositoryInterface $key;

  /**
   * WaterAidWebformStripeService constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\stripe_api\StripeApiService $stripe_api
   *   Stripe API service.
   * @param \Drupal\key\KeyRepositoryInterface $key
   *   The Key Repository.
   */
  public function __construct(LoggerInterface $logger, StripeApiService $stripe_api, KeyRepositoryInterface $key) {
    $this->logger = $logger;
    $this->stripeApi = $stripe_api;
    $this->key = $key;
  }

  /**
   * Get a Stripe Client.
   *
   * This call wraps StripeApiService::getStripeClient().
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   a Webform instance.
   *
   * @return \Stripe\StripeClient
   *   The StripeClient.
   */
  public function getStripeClient(WebformInterface $webform): StripeClient {
    $config_key = $this->stripeApi->getMode() . '_secret_key';
    // @see wateraid_donation_stripe_webform_third_party_settings_form_alter().
    $key_id = $webform->getThirdPartySetting('wateraid_donation_stripe', $config_key);
    if ($key_id) {
      $key_entity = $this->key->getKey($key_id);
      if ($key_entity) {
        // Return a StripeClient with overridden API secret key.
        return $this->stripeApi->getStripeClient([
          'api_key' => $key_entity->getKeyValue(),
        ]);
      }
    }
    // If no valid details are attainable for the Webform then return default.
    return $this->stripeApi->getStripeClient();
  }

  /**
   * Retrieve the Stripe API Public Key.
   *
   * This call wraps StripeApiService::getPubKey(). Provides a fallback if not
   * specified to the default Stripe API Service lookup functionality.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   a Webform instance.
   *
   * @return string|null
   *   Either NULL or an API key in string format.
   */
  public function getPubKey(WebformInterface $webform): ?string {
    $config_key = $this->stripeApi->getMode() . '_public_key';
    // @see wateraid_donation_stripe_webform_third_party_settings_form_alter().
    $key_id = $webform->getThirdPartySetting('wateraid_donation_stripe', $config_key);
    if ($key_id) {
      $key_entity = $this->key->getKey($key_id);
      if ($key_entity) {
        return $key_entity->getKeyValue();
      }
    }
    // Fallback to default Stripe API service key retrieval func.
    return $this->stripeApi->getPubKey();
  }

}
