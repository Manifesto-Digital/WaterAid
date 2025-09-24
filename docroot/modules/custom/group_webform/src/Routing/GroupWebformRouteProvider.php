<?php

namespace Drupal\group_webform\Routing;

use Symfony\Component\Routing\Route;

/**
 * Provides routes for group_webform group relationship.
 */
class GroupWebformRouteProvider {

  /**
   * Provides the shared collection route for group webform plugins.
   */
  public function getRoutes() {
    $routes = $plugin_ids = $permissions_add = $permissions_create = [];

    $plugin_id = "group_webform:webform";
    $plugin_ids[] = $plugin_id;
    $permissions_add[] = "create $plugin_id relationship";
    $permissions_create[] = "create $plugin_id entity";

    $routes['entity.group_relationship.group_webform_relate_page'] = new Route('group/{group}/webform/add');
    $routes['entity.group_relationship.group_webform_relate_page']
      ->setDefaults([
        '_title' => 'Relate webform',
        '_controller' => '\Drupal\group_webform\Controller\GroupWebformController::addPage',
        'base_plugin_id' => 'group_webform',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_add))
      ->setRequirement('_group_installed_relationship', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    $routes['entity.group_relationship.group_webform_add_page'] = new Route('group/{group}/webform/create');
    $routes['entity.group_relationship.group_webform_add_page']
      ->setDefaults([
        '_title' => 'Create webform',
        '_controller' => '\Drupal\group_webform\Controller\GroupWebformController::addPage',
        'create_mode' => TRUE,
        'base_plugin_id' => 'group_webform',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_create))
      ->setRequirement('_group_installed_relationship', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
