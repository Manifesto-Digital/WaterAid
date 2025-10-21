<?php

namespace Drupal\wateraid_donation_forms\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    $routes = [
      'entity.webform.confirmation',
      'entity.node.webform.confirmation',
    ];

    foreach ($routes as $route_id) {
      if ($route = $collection->get($route_id)) {
        $route->setDefault('_controller', '\Drupal\wateraid_donation_forms\Controller\WebformController::confirmation');

        // Set webform confirm pages to not redirect (via the "normalizer").
        // We've set multiple path aliases for them, so we want to specify the
        // exact redirect to use (depending on the user selected frequency).
        //
        // @See wateraid_donation_forms_webform_update().
        // @See RouteNormalizerRequestSubscriber::onKernelRequestRedirect().
        // @See DonationsWebformHandler::confirmForm().
        $route->setDefault('_disable_route_normalizer', FALSE);
      }
    }
  }

}
