<?php

/**
 * @file
 * General WaterAid updates to run after config import.
 */

use Drupal\Core\Utility\Error as DCUError;

/**
 * Populate the hero link style field with data from the old field.
 */
function wateraid_base_core_deploy_populate_hero_link_style_field(&$sandbox): mixed {
  if (!isset($sandbox['total'])) {
    $items = \Drupal::entityQuery('paragraph')
      ->condition('type', 'hero')
      ->accessCheck(FALSE)
      ->execute();
    $sandbox['total'] = count($items);
    $sandbox['items'] = $items;
    $sandbox['current'] = 0;
    $sandbox['debug_log'] = [];

    if (empty($sandbox['total'])) {
      $sandbox['#finished'] = 1;
      return '';
    }
  }

  $batch_size = 25;
  $item_ids = array_slice($sandbox['items'], $sandbox['current'], $batch_size);
  if (empty($item_ids)) {
    $sandbox['#finished'] = 1;
  }

  foreach ($item_ids as $item_id) {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($item_id);

    if ($current_colour = $paragraph->get('field_colour_selector')->value) {
      if ($paragraph->get('field_limited_colour_select')) {
        $logger = \Drupal::logger('Update');
        switch ($current_colour) {
          case 'wa-black':
            // wa-black to navy.
            $paragraph->set('field_limited_colour_select', 'navy');
            try {
              $paragraph->save();
              $sandbox['debug_log'][$item_id] = 'Set colour from ' . $current_colour . ' to navy';
            }
            catch (\Exception $exception) {
              $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $current_colour;
              DCUError::logException($logger, $exception);
            }
            break;

          case 'wa-blue':
            // wa-blue to blue.
            $paragraph->set('field_limited_colour_select', 'blue');
            try {
              $paragraph->save();
              $sandbox['debug_log'][$item_id] = 'Set colour from ' . $current_colour . ' to blue';
            }
            catch (\Exception $exception) {
              $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $current_colour;
              DCUError::logException($logger, $exception);
            }
            break;

          case 'wa-white':
            // Set new field to the same wa-white.
            $paragraph->set('field_limited_colour_select', 'wa-white');
            try {
              $paragraph->save();
              $sandbox['debug_log'][$item_id] = 'Set colour from ' . $current_colour . ' to wa-white';
            }
            catch (\Exception $exception) {
              $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $current_colour;
              DCUError::logException($logger, $exception);
            }
            break;

          default:
            // Change secondary colours to lime.
            $paragraph->set('field_limited_colour_select', 'lime');
            try {
              $paragraph->save();
              $sandbox['debug_log'][$item_id] = 'Set colour from ' . $current_colour . ' to lime';
            }
            catch (\Exception $exception) {
              $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $current_colour;
              DCUError::logException($logger, $exception);
            }
            break;
        }
      }
    }

    $sandbox['current']++;
  }

  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;
    return "Debug log: " . print_r($sandbox['debug_log'], TRUE);
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }

  return "Processed " . $sandbox['current'] . "/" . $sandbox['total'];
}

/**
 * Set the background colour for listing module.
 */
function wateraid_base_core_deploy_listing_set_background(&$sandbox): mixed {
  if (!isset($sandbox['total'])) {
    $items = \Drupal::entityQuery('paragraph')
      ->condition('type', 'listing')
      ->accessCheck(FALSE)
      ->execute();
    $sandbox['total'] = count($items);
    $sandbox['items'] = $items;
    $sandbox['current'] = 0;
    $sandbox['debug_log'] = [];

    if (empty($sandbox['total'])) {
      $sandbox['#finished'] = 1;
      return '';
    }
  }

  $batch_size = 25;
  $item_ids = array_slice($sandbox['items'], $sandbox['current'], $batch_size);
  if (empty($item_ids)) {
    $sandbox['#finished'] = 1;
  }

  foreach ($item_ids as $item_id) {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($item_id);

    // For listing modules that use a background, set
    // the new colour field to navy.
    if ($paragraph->get('field_listing_style')->value === 'background') {
      $first_listing_item = $paragraph->get('field_listing_item')->first();
      $first_listing_item_colour = $first_listing_item->entity->get('field_colour_selector')->first();
      $paragraph->set('field_colour_selector', [$first_listing_item_colour->value]);
      try {
        $paragraph->save();
        $sandbox['debug_log'][$item_id] = 'Default background colour';
      }
      catch (\Exception $exception) {
        $sandbox['debug_log'][$item_id] = 'Failed to set default';
        $logger = \Drupal::logger('Update');
        DCUError::logException($logger, $exception);
      }
    }
    else {
      $sandbox['debug_log'][$item_id] = 'Skipped - no background';
    }

    $sandbox['current']++;
  }

  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;
    return "Debug log: " . print_r($sandbox['debug_log'], TRUE);
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }

  return "Processed " . $sandbox['current'] . "/" . $sandbox['total'];
}

/**
 * Set the default background colour for listing module.
 */
function wateraid_base_core_deploy_listing_apply_background_value(&$sandbox): mixed {
  if (!isset($sandbox['total'])) {
    $items = \Drupal::entityQuery('paragraph')
      ->condition('type', 'listing')
      ->accessCheck(FALSE)
      ->execute();
    $sandbox['total'] = count($items);
    $sandbox['items'] = $items;
    $sandbox['current'] = 0;
    $sandbox['debug_log'] = [];

    if (empty($sandbox['total'])) {
      $sandbox['#finished'] = 1;
      return '';
    }
  }

  $batch_size = 25;
  $item_ids = array_slice($sandbox['items'], $sandbox['current'], $batch_size);
  if (empty($item_ids)) {
    $sandbox['#finished'] = 1;
  }

  foreach ($item_ids as $item_id) {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($item_id);

    // For listing modules that don't have a "Background Colour" set
    // we need to set this to a default of white owing to the field
    // now being required (see: WMS-3196).
    if (is_null($paragraph->get('field_colour_selector')->value)) {
      $paragraph->set('field_colour_selector', 'wa-white');
      try {
        $paragraph->save();
        $sandbox['debug_log'][$item_id] = 'No background colour set, setting white as the default';
      }
      catch (\Exception $exception) {
        $sandbox['debug_log'][$item_id] = 'Failed to set default';
        $logger = \Drupal::logger('Update');
        DCUError::logException($logger, $exception);
      }
    }
    else {
      $sandbox['debug_log'][$item_id] = 'Skipped - background colour already set';
    }

    $sandbox['current']++;
  }

  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;
    return "Debug log: " . print_r($sandbox['debug_log'], TRUE);
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }

  return "Processed " . $sandbox['current'] . "/" . $sandbox['total'];
}
