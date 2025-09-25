<?php

namespace Drupal\wateraid_donation_paypal\Event;

use Drupal\Component\EventDispatcher\Event;
use PayPal\Api\WebhookEvent as PayPalWebhookEvent;

/**
 * Class PayPalApiWebhookEvent.
 *
 * Provides the PayPal API Webhook Event.
 */
class PayPalApiWebhookEvent extends Event {

  /**
   * Type.
   */
  public string $type;

  /**
   * Data.
   *
   * @var mixed[]
   */
  public array $data;

  /**
   * Event.
   */
  public PayPalWebhookEvent $event;

  /**
   * Sets the default values for the event.
   *
   * @param string $type
   *   Webhook event type.
   * @param mixed[] $data
   *   Webhook event data.
   * @param \PayPal\Api\WebhookEvent|null $event
   *   PayPal webhook event object.
   */
  public function __construct(string $type, array $data, ?PayPalWebhookEvent $event = NULL) {
    $this->type = $type;
    $this->data = $data;
    $this->event = $event;
  }

}
