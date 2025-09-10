<?php

namespace Drupal\wateraid_donation_stripe\Plugin\PaymentProvider;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wateraid_donation_forms\Exception\PaymentException;
use Drupal\wateraid_donation_forms\PaymentProviderBase;
use Drupal\wateraid_donation_stripe\WaterAidWebformStripeService;
use Drupal\webform\WebformInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Test Payment provider.
 *
 * @package Drupal\wateraid_donation_stripe\Plugin\PaymentProvider
 */
abstract class StripePaymentProviderBase extends PaymentProviderBase {

  /**
   * Stripe API configuration object.
   */
  protected ImmutableConfig $stripeApiConfig;

  /**
   * The WaterAidWebformStripeService.
   */
  protected WaterAidWebformStripeService $webformStripeService;

  /**
   * A logger instance.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config_factory, WaterAidWebformStripeService $webform_stripe_service, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stripeApiConfig = $config_factory->get('stripe_api.settings');
    $this->webformStripeService = $webform_stripe_service;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('wateraid_webform_stripe'),
      $container->get('logger.channel.wateraid_donation_stripe')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processWebformComposite(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->getFormObject()->getEntity()->getWebform();
    $element['#attached']['drupalSettings']['webformStripe']['public_key'] = $this->webformStripeService->getPubKey($webform);
    $element['#attached']['drupalSettings']['webformStripe']['api_version'] = $this->stripeApiConfig->get('api_version') === 'custom' ? $this->stripeApiConfig->get('api_version_custom') : NULL;
    $element['#attached']['library'][] = 'wateraid_donation_stripe/stripe.apijs';
  }

  /**
   * {@inheritdoc}
   */
  public function processPayment(array $payment, ?WebformInterface $webform = NULL): mixed {
    // Fail if no payment response details.
    if (!empty($payment['amount']) && $payment['payment_response'] === NULL) {
      throw new PaymentException('Amount not paid');
    }
    // Fail on any error.
    if (!empty($payment['payment_response']['error'])) {
      $this->logger->error('Error: @error <br /> @args', [
        '@args' => Json::encode([
          'object' => 'PaymentIntent',
          'method' => 'create',
          'type' => $payment['payment_response']['error']['type'],
          'code' => $payment['payment_response']['error']['code'],
          'doc_url' => $payment['payment_response']['error']['doc_url'],
          'params' => $payment['payment_response']['error']['payment_intent'],
        ]),
        '@error' => $payment['payment_response']['error']['message'],
      ]);
      throw new PaymentException('Error received from payment');
    }
    // Payment intent id is mandatory.
    if (empty($payment['payment_response']['paymentIntent']['id'])) {
      throw new PaymentException('Missing Payment Intent');
    }
    return $payment['payment_response']['paymentIntent'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId($result): string {
    return $result->id;
  }

  /**
   * Gather customer data to prepare for metadata.
   *
   * Stripe can only accept a one level array, so we need to flatten it.
   *
   * @param mixed[] $payment
   *   The payment array.
   *
   * @return mixed[]
   *   Returns an array of customer data.
   */
  protected function getCustomerData(array $payment): array {
    $customer_data = [];
    foreach ($payment['customer'] as $customer_field_name => $customer_field_value) {
      if (is_array($customer_field_value)) {
        $customer_data += $customer_field_value;
      }
      else {
        $customer_data[$customer_field_name] = $customer_field_value;
      }
    }
    return $customer_data;
  }

}
