<?php

declare(strict_types=1);

namespace Drupal\group_webform\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
final class GroupWebformRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {

    // Hide unneeded tabs.
    foreach ([
      'entity.group_relationship.collection',
      'entity.group_content_menu.add_menu_link',
      'entity.group_content_menu.collection',
    ] as $route_name) {
      if ($route = $collection->get($route_name)) {
        $route->setRequirement('_access', 'FALSE');
      }
    }

    // Ensure routes use the admin theme.
    foreach ([
      'entity.group.version_history',
      'view.group_members.page_1',
      'view.group_node.page_1',
      'entity.group.webform',
    ] as $route_name) {
      if ($route = $collection->get($route_name)) {
        $route->setOption('_admin_route', TRUE);
      }
    }
  }

}
