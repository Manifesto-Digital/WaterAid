<?php

namespace Drupal\wateraid_base_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters the access permissions on Preview Link routes.
 */
class PreviewRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    foreach ($collection->all() as $route_id => $route) {
      if (str_contains($route_id, '.preview_link_generate')) {
        $route->addRequirements(['_wateraid_access_generate_preview' => 'TRUE']);
        $route->setOption('_admin_route', TRUE);
      }
      elseif (str_contains($route_id, '.preview_link')) {
        $route->addRequirements(['_wateraid_access_view_preview' => 'TRUE']);
      }
    }
  }

}
