<?php

namespace Drupal\wateraid_forms\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * WaterAid Webforms event subscriber.
 */
class WateraidWebformSubscriber implements EventSubscriberInterface {

  /**
   * The current route match.
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Constructs a WateraidWebformSubscriber object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Kernel response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   Response event.
   */
  public function onKernelResponse(ResponseEvent $event): void {
    $is_webform = FALSE;
    $route_name = $this->routeMatch->getRouteName();
    if ($route_name == 'entity.webform.canonical') {
      // This is a webform canonical route.
      $is_webform = TRUE;
    }
    elseif ($route_name == 'entity.node.canonical') {
      $request = $event->getRequest();
      /** @var \Drupal\node\NodeInterface $node */
      $node = $request->attributes->get('node');
      if ($node->bundle() == 'webform') {
        // This is a Webform node.
        $is_webform = TRUE;
      }
    }

    if ($is_webform) {
      /*
       * Add the "no-store" cache control header to Webforms to prevent
       * issues with the browser back button. This ensures that the user
       * can navigate back to the previous step, then progress forwards
       * through the form again.
       */
      $event->getResponse()->headers->addCacheControlDirective('no-store', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => ['onKernelResponse'],
    ];
  }

}
