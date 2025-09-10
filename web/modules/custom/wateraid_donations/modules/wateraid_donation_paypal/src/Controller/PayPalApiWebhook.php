<?php

namespace Drupal\wateraid_donation_paypal\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\wateraid_donation_paypal\Event\PayPalApiWebhookEvent;
use Drupal\wateraid_donation_paypal\PayPalApiService;
use PayPal\Api\WebhookEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PayPalApiWebhook.
 *
 * Provides the route functionality for paypal_api.webhook route.
 */
class PayPalApiWebhook extends ControllerBase {

  /**
   * Fake ID from Stripe we can check against.
   */
  const FAKE_EVENT_ID = 'evt_00000000000000';

  /**
   * PayPal API service.
   */
  protected PayPalApiService $paypalApi;

  /**
   * The event dispatcher service.
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(PayPalApiService $paypal_api, EventDispatcherInterface $eventDispatcher) {
    $this->paypalApi = $paypal_api;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('wateraid_donation_paypal.paypal_api'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Captures the incoming webhook request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response.
   */
  public function handleIncomingWebhook(Request $request): Response {

    if (str_starts_with($request->headers->get('Content-Type'), 'application/json')) {

      $input = $request->getContent();
      $decoded_input = json_decode($input);
      $config = $this->config('wateraid_donation_paypal.settings');
      $mode = $config->get('mode') ?: 'test';

      if (!$event = $this->isValidWebhook($mode, $decoded_input)) {
        $this->getLogger('paypal_api')
          ->error('Invalid webhook event: @data', [
            '@data' => $input,
          ]);
        return new Response(NULL, Response::HTTP_FORBIDDEN);
      }

      /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
      $logger = $this->getLogger('paypal_api');
      $logger->info("PayPal webhook received event:\n @event", ['@event' => (string) $event]);

      // Dispatch the webhook event.
      $e = new PayPalApiWebhookEvent($event->type, $decoded_input->data, $event);
      $this->eventDispatcher->dispatch('paypal_api.webhook', $e);

      return new Response('Okay', Response::HTTP_OK);
    }

    return new Response(NULL, Response::HTTP_FORBIDDEN);
  }

  /**
   * Determines if a webhook is valid.
   *
   * @param string $mode
   *   PayPal API mode. Either 'live' or 'test'.
   * @param object $data
   *   PayPal event object parsed from JSON.
   *
   * @return bool|\PayPal\Api\WebhookEvent
   *   Returns TRUE if the webhook is valid or the Stripe Event object.
   */
  private function isValidWebhook(string $mode, object $data): bool|WebhookEvent {
    return FALSE;
  }

}
