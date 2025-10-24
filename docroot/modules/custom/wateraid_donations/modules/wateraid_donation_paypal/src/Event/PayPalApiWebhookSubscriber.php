<?php

namespace Drupal\wateraid_donation_paypal\Event;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PayPalApiWebhookSubscriber.
 *
 * Provides the webhook subscriber functionality.
 */
class PayPalApiWebhookSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   */
  protected ConfigFactory $configFactory;

  /**
   * Logger.
   */
  protected LoggerChannelInterface $logger;

  /**
   * PayPalApiWebhookSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelInterface $logger) {
    $this->config = $config_factory->get('wateraid_donation_paypal.settings');
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events['paypal_api.webhook'][] = ['onIncomingWebhook'];
    return $events;
  }

  /**
   * Process an incoming webhook.
   *
   * @param \Drupal\wateraid_donation_paypal\Event\PayPalApiWebhookEvent $event
   *   Logs an incoming webhook of the setting is on.
   */
  public function onIncomingWebhook(PayPalApiWebhookEvent $event): void {

    if ($this->config->get('log_webhooks') ?: TRUE) {
      $this->logger->notice('Processed webhook: @name<br /><br />Data: @data', [
        '@name' => $event->type,
        '@data' => Json::encode($event->data),
      ]);
    }
  }

}
