<?php

declare(strict_types=1);

namespace Drupal\wateraid_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber to add group parameter to node routes.
 *
 * This adds the 'group' parameter definition to the node canonical route,
 * telling Drupal to use our GroupFromNodeParamConverter to derive the group
 * from the node.
 */
final class GroupNodeRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('entity.node.canonical')) {
      // Add a 'group' parameter that will be converted using our custom
      // param converter. The converter receives the node entity and returns
      // the group.
      $route->setOption('parameters', array_merge(
        $route->getOption('parameters') ?? [],
        [
          'group' => [
            'type' => 'group_from_node',
            // Tell the converter to use the 'node' parameter value.
            'converter' => 'wateraid_group.group_from_node_param_converter',
          ],
        ]
      ));

      // Set a non-NULL default so the param converter gets invoked.
      // The converter will replace this with the actual group entity.
      $route->setDefault('group', FALSE);
    }
  }

}
