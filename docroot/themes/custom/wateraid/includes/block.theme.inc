<?php

/**
 * @file
 * Hooks pertaining to blocks.
 */

use Drupal\config_pages\ConfigPagesInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_content_menu\GroupContentMenuInterface;

/**
 * Implements hook_preprocess_HOOK() for blocks.
 */
function wateraid_preprocess_block(&$variables): void {
  if ($variables['plugin_id'] === 'system_menu_block:site-selector') {
    // If site selector config pages values change, invalidate the block cache.
    $site_selector_config_page = config_pages_config('global_site_selector');
    if ($site_selector_config_page instanceof ConfigPagesInterface) {
      $variables['#cache']['tags'][] = 'config_pages:' . $site_selector_config_page->id();
    }
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function wateraid_preprocess_block__wateraid_site_footer(&$variables): void {
  $group = \Drupal::routeMatch()->getParameters()->get('group');

  if ($group instanceof GroupInterface) {
    wateraid_add_group_menus($group, [
      'footer_primary_menu' => 'group_content_menu:footer_primary_menu',
      'footer_secondary_menu' => 'group_content_menu:footer_secondary_menu',
    ], $variables);
  }
  else {
    $variables['footer_primary_menu'] = _wateraid_build_menu('footer', 1);
    $variables['footer_secondary_menu'] = _wateraid_build_menu('secondary-footer', 1);
  }

  $storage = \Drupal::entityTypeManager()->getStorage('config_pages');
  $map = [
    'footer' => [
      'field_donate_link',
      'field_phone_number',
      'field_email',
      'field_address',
      'field_copy',
      'field_logos',
    ],
    'site_settings' => [
      'field_facebook',
      'field_instagram',
      'field_tiktok',
      'field_linkedin',
      'field_youtube',
      'field_site_logo',
    ],
  ];

  foreach (['tags', 'contexts'] as $tag) {
    if (!isset($variables['#cache'][$tag])) {
      $variables['#cache'][$tag] = [];
    }
  }

  foreach ($map as $config_page_id => $fields) {

    /** @var \Drupal\config_pages\ConfigPagesInterface $config_page */
    if ($config_page = $storage->load($config_page_id)) {
      $variables['#cache']['tags'] = array_merge($variables['#cache']['tags'], $config_page->getCacheTags());
      $variables['#cache']['contexts'] = array_merge($variables['#cache']['contexts'], $config_page->getCacheContexts());

      foreach ($fields as $field) {
        $variables[$field] = $config_page->get($field)->view('full');

        CacheableMetadata::createFromRenderArray($variables[$field])
          ->addCacheableDependency($config_page)
          ->applyTo($variables[$field]);
      }
    }
  }
}
