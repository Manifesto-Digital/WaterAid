<?php

/**
 * @file
 * Hooks pertaining to blocks.
 */

use Drupal\config_pages\ConfigPagesInterface;
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
            $variables[$var_name] = _wateraid_build_menu($menu_name, 1);
            // Add cacheability metadata.
            $variables['#cache']['tags'][] = 'group_content_menu:' . $group_content_menu->id();
          }
        }
      }
    }
  }

  if ($variables['plugin_id'] === 'system_menu_block:site-selector') {
    // If site selector config pages values change, invalidate the block cache.
    $site_selector_config_page = config_pages_config('global_site_selector');
    if ($site_selector_config_page instanceof ConfigPagesInterface) {
      $variables['#cache']['tags'][] = 'config_pages:' . $site_selector_config_page->id();
    }
  }
}
