<?php

/**
 * @file
 * Hooks pertaining to blocks.
 */

use Drupal\group\Entity\GroupInterface;
use Drupal\group_content_menu\GroupContentMenuInterface;

/**
 * Implements hook_preprocess_HOOK() for blocks.
 */
function wateraid_preprocess_block(&$variables) {
  if ($variables['plugin_id'] === 'wateraid_site_footer') {
    $group = \Drupal::routeMatch()->getParameters()->get('group');
    $footer_menus = [
      'footer_primary_menu' => 'group_content_menu:footer_primary_menu',
      'footer_secondary_menu' => 'group_content_menu:footer_secondary_menu',
    ];

    if ($group instanceof GroupInterface) {
      $group_menu_relationships = group_content_menu_get_menus_per_group($group);
      foreach ($group_menu_relationships as $group_menu_relationship) {
        foreach ($footer_menus as $var_name => $plugin_id) {
          if ($group_menu_relationship->getPluginId() === $plugin_id) {
            /** @var \Drupal\group_content_menu\GroupContentMenuInterface $group_content_menu */
            $group_content_menu = $group_menu_relationship->getEntity();
            // The menu name is derived from the group menu content entity.
            $menu_name = GroupContentMenuInterface::MENU_PREFIX . $group_content_menu->id();
            /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree */
            $menu_tree = \Drupal::service('menu.link_tree');
            $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
            $parameters->setMaxDepth(1);
            // Load the tree and apply manipulators.
            $tree = $menu_tree->load($menu_name, $parameters);
            $manipulators = [
              ['callable' => 'menu.default_tree_manipulators:checkAccess'],
              ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
            ];
            $tree = $menu_tree->transform($tree, $manipulators);
            // Build as renderable.
            $menu = $menu_tree->build($tree);
            $variables[$var_name] = $menu;
          }
        }

      }
    }
  }
}
