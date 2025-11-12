<?php

/**
 * @file
 * Theme hooks pertaining to paragraphs and their derivatives.
 */

use Drupal\paragraphs\ParagraphInterface;
use Drupal\Component\Utility\Html;

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

  $variables['#cache']['contexts'][] = 'url';
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
