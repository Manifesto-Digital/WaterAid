<?php

declare(strict_types=1);

namespace Drupal\wateraid_phased_launch\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
final class WateraidPhasedLaunchRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {

    // Use a custom path for Views AJAX to prevent it getting rerouted to the
    // live site.
    foreach (['media.oembed_iframe', 'views.ajax'] as $route_name) {
      if ($route = $collection->get($route_name)) {
        $path = $route->getPath();
        $route->setPath('/wateraid-donation-v2' . $path);
      }
    }
  }

}
