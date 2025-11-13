<?php

/**
 * @file
 * Menu theme hooks.
 */

use Drupal\config_pages\ConfigPagesInterface;

/**
 * Implements hook_preprocess_HOOK() for menu.
 */
function wateraid_preprocess_menu(&$variables) {

  if ($variables['menu_name'] === 'site-selector') {
    $grouped_items = [];
    foreach ($variables['items'] as $key => $item) {
      /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link_content */
      $menu_link_content = $item['entity'];
      /** @var \Drupal\Core\Template\Attribute $menu_item_attrs */
      $menu_item_attrs = $item['attributes'];

      // Add class to each menu item for flags.
      if (!$menu_link_content->get('field_country_class')->isEmpty()) {
        $menu_item_attrs->addClass('site-selector__country--' . $menu_link_content->get('field_country_class')->first()->value);
      }

      // Group menu items by region.
      $group_id = !$menu_link_content->get('field_region')->isEmpty() ? $menu_link_content->get('field_region')->first()->value : NULL;
      $field_region_options = $menu_link_content->getFieldDefinition('field_region')->getItemDefinition()->getSetting('allowed_values');

      $group = &$grouped_items[$group_id ?? 'global'];

      if (empty($subgroup)) {
        $group['title'] = $group_id ? $field_region_options[$group_id] : NULL;
      }
      $group['items'][$key] = $item;
    }

    // For each group, sort the items alphabetically.
    foreach ($grouped_items as &$group) {
      uasort($group['items'], function ($a, $b) {
        return strcasecmp($a['title'], $b['title']);
      });
    }

    $variables['grouped_items'] = $grouped_items;

    // Get config pages field data for panel.
    $site_selector_config_page = config_pages_config('global_site_selector');
    if ($site_selector_config_page instanceof ConfigPagesInterface) {
      $variables['panel_title'] = $site_selector_config_page->get('field_title')->value;
      $variables['panel_description'] = config_pages_config('global_site_selector')->get('field_description')->value;
    }
  }
}
