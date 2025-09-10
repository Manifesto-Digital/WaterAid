<?php

namespace Drupal\wateraid_site_manager\Routing;

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
    // Avoid permission error using the "Hide descriptions" link on the WaterAid
    // admin overview page.
    $this->addPermissionToRoute($collection, 'system.admin_compact_page', 'access wateraid administration pages');

    // Avoid permission denied error after enabling module without
    // "administer modules" permission which WA site admin shouldn't have.
    $this->addPermissionToRoute($collection, 'system.modules_list_confirm', 'administer wateraid');

    // Add wateraid permission to the root admin page.
    $this->addPermissionToRoute($collection, 'system.admin', 'access wateraid administration pages');
  }

  /**
   * Add a permission to an existing route.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The RouteCollection object.
   * @param string $route_id
   *   The route to add a permission to.
   * @param string $permission
   *   The permission to add.
   */
  private function addPermissionToRoute(RouteCollection $collection, string $route_id, string $permission): void {
    /** @var \Symfony\Component\Routing\Route $route */
    if ($route = $collection->get($route_id)) {
      $route->addRequirements([
        '_permission' => $permission,
      ]);
    }
  }

}
