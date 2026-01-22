<?php

/**
 * @file
 * Theme hooks pertaining to paragraphs and their derivatives.
 */

use Drupal\paragraphs\ParagraphInterface;
use Drupal\Component\Utility\Html;
use Drupal\group\Entity\Group;

/**
 * Implements hook_preprocess_HOOK().
 */
function wateraid_preprocess_paragraph(&$variables): void {
  if ($variables['paragraph']->bundle() == 'quote') {
    $variables['attributes']['class'][] = 'quote-type--' . Html::cleanCssIdentifier($variables['paragraph']->get('field_quote_type')->getString());
  }

  $request = \Drupal::request();
  $route_match = \Drupal::routeMatch();

  $variables['page_title'] = ($route = $route_match->getRouteObject()) ? \Drupal::service('title_resolver')->getTitle($request, $route) : '';

  // Override page title for group pages in full view mode
  if ($route_match && $route_match->getRouteName() === 'entity.group.canonical' && $route_match->getParameter('group')) {
    $group = $route_match->getParameter('group');
    if ($group instanceof Group) {
      // Check if we're viewing the group in full view mode
      $view_mode = $route_match->getParameter('view_mode');
      if ($view_mode === 'full' || !$view_mode) { // Default view mode is typically 'full'
        if ($group->hasField('field_title') && !$group->get('field_title')->isEmpty()) {
          $variables['page_title'] = $group->get('field_title')->value;
        }
      }
    }
  }

  $variables['#cache']['contexts'][] = 'url';
  if ($variables['paragraph']->bundle() == 'hero_image' || $variables['paragraph']->bundle() == 'hero_donate') {
    if (isset($variables['content']['field_image_crop'][0]['#context'])) {
      $crop_json = $variables['content']['field_image_crop'][0]['#context']['value'];
      $crop_array = json_decode($crop_json, TRUE);

      $variables['image_crop_data'] = $crop_array;
    }
  }
}

/**
 * Implements hook_preprocess_HOOK() for paragraph type 'image'.
 */
function wateraid_preprocess_paragraph__image(&$variables): void {
  /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
  $paragraph = $variables['paragraph'];
  $parent = $paragraph->getParentEntity();
  // For image paragraphs referenced from an image_gallery paragraph, we want to
  // force an arbitrary width for images, any other width names will mess with
  // the gallery implementation.
  if ($parent instanceof ParagraphInterface && $parent->bundle() === 'image_gallery') {
    $variables['force_width'] = 'gallery-image';
  }
}
