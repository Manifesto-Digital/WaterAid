<?php

/**
 * @file
 * Updates for paragraph_colours.
 */

use Drupal\Core\Utility\Error as DCUError;

/**
 * Remove tertiary colour options.
 *
 * @param mixed[] $sandbox
 *   Sandbox array.
 *
 * @return string
 *   Complete message.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function paragraphs_colour_post_update_colours(array &$sandbox): string {
  // Switch tertiary for appropriate secondary colours before theyre removed.
  if (!isset($sandbox['total'])) {
    $items = \Drupal::entityQuery('paragraph')
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
    if ($paragraph->getBehaviorSetting('paragraph_colour', 'paragraph_color')) {
      $colour = $paragraph->getBehaviorSetting('paragraph_colour', 'paragraph_color');
      $logger = \Drupal::logger('Update');

      if ($colour === 'tertiary-light-brown') {
        // tertiary-light-brown to secondary-brown.
        $paragraph->setBehaviorSettings('paragraph_colour', ['paragraph_color' => 'secondary-brown']);
        try {
          $paragraph->save();
          $sandbox['debug_log'][$item_id] = 'Set new colour to secondary-brown';
        }
        catch (\Exception $exception) {
          $sandbox['debug_log'][$item_id] = 'Failed to set new colour';
          DCUError::logException($logger, $exception);
        }
      }
      elseif ($colour === 'deep-cyan') {
        // deep-cyan to secondary-navy-green.
        $paragraph->setBehaviorSettings('paragraph_colour', ['paragraph_color' => 'secondary-navy-green']);
        try {
          $paragraph->save();
          $sandbox['debug_log'][$item_id] = 'Set new colour to secondary-navy-green';
        }
        catch (\Exception $exception) {
          $sandbox['debug_log'][$item_id] = 'Failed to set new colour';
          DCUError::logException($logger, $exception);
        }
      }
      elseif ($colour === 'deep-purple') {
        // deep-purple to secondary-purple.
        $paragraph->setBehaviorSettings('paragraph_colour', ['paragraph_color' => 'secondary-purple']);
        try {
          $paragraph->save();
          $sandbox['debug_log'][$item_id] = 'Set new colour secondary-purple';
        }
        catch (\Exception $exception) {
          $sandbox['debug_log'][$item_id] = 'Failed to set new colour';
          DCUError::logException($logger, $exception);
        }
      }
      else {
        $sandbox['debug_log'][$item_id] = 'Skipped - not tertiary colour';
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
 * Switch to brand refresh colours.
 *
 * @param mixed[] $sandbox
 *   Sandbox array.
 *
 * @return string
 *   Complete message.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function paragraphs_colour_post_update_brand_refresh(array &$sandbox): string {
  if (!isset($sandbox['total'])) {
    $items = \Drupal::entityQuery('paragraph')
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
    if ($paragraph->getBehaviorSetting('paragraph_colour', 'paragraph_color')) {
      $colour = $paragraph->getBehaviorSetting('paragraph_colour', 'paragraph_color');
      $logger = \Drupal::logger('Update');

      switch ($colour) {
        case 'wa-black':
          // wa-black to navy.
          $paragraph->setBehaviorSettings('paragraph_colour', ['paragraph_color' => 'navy']);
          try {
            $paragraph->save();
            $sandbox['debug_log'][$item_id] = 'Set colour from wa-black to navy';
          }
          catch (\Exception $exception) {
            $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
            DCUError::logException($logger, $exception);
          }
          break;

        case 'wa-blue':
        case 'secondary-blue':
          // wa-blue and secondary-blue to blue.
          $paragraph->setBehaviorSettings('paragraph_colour', ['paragraph_color' => 'blue']);
          try {
            $paragraph->save();
            $sandbox['debug_log'][$item_id] = 'Set colour from ' . $colour . ' to blue';
          }
          catch (\Exception $exception) {
            $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
            DCUError::logException($logger, $exception);
          }
          break;

        case 'secondary-orange':
          // secondary-orange to yellow.
          $paragraph->setBehaviorSettings('paragraph_colour', ['paragraph_color' => 'yellow']);
          try {
            $paragraph->save();
            $sandbox['debug_log'][$item_id] = 'Set colour from secondary-orange to yellow';
          }
          catch (\Exception $exception) {
            $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
            DCUError::logException($logger, $exception);
          }
          break;

        case 'secondary-light-green':
          // secondary-light-green to light-green.
          $paragraph->setBehaviorSettings('paragraph_colour', ['paragraph_color' => 'light-green']);
          try {
            $paragraph->save();
            $sandbox['debug_log'][$item_id] = 'Set colour from secondary-light-green to light-green';
          }
          catch (\Exception $exception) {
            $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
            DCUError::logException($logger, $exception);
          }
          break;

        case 'secondary-dark-green':
        case 'secondary-navy-green':
          // secondary-dark-green and secondary-navy-green to dark-green.
          $paragraph->setBehaviorSettings('paragraph_colour', ['paragraph_color' => 'dark-green']);
          try {
            $paragraph->save();
            $sandbox['debug_log'][$item_id] = 'Set colour from ' . $colour . ' to dark-green';
          }
          catch (\Exception $exception) {
            $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
            DCUError::logException($logger, $exception);
          }
          break;

        case 'secondary-fuscia':
          // secondary-fuscia to pink.
          $paragraph->setBehaviorSettings('paragraph_colour', ['paragraph_color' => 'pink']);
          try {
            $paragraph->save();
            $sandbox['debug_log'][$item_id] = 'Set colour from secondary-fuscia to pink';
          }
          catch (\Exception $exception) {
            $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
            DCUError::logException($logger, $exception);
          }
          break;

        case 'secondary-purple':
          // secondary-purple to plum.
          $paragraph->setBehaviorSettings('paragraph_colour', ['paragraph_color' => 'plum']);
          try {
            $paragraph->save();
            $sandbox['debug_log'][$item_id] = 'Set colour from secondary-purple to plum';
          }
          catch (\Exception $exception) {
            $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
            DCUError::logException($logger, $exception);
          }
          break;

        case 'secondary-red':
        case 'secondary-brown':
          // secondary-red and secondary-brown to orange.
          $paragraph->setBehaviorSettings('paragraph_colour', ['paragraph_color' => 'orange']);
          try {
            $paragraph->save();
            $sandbox['debug_log'][$item_id] = 'Set colour from ' . $colour . ' to orange';
          }
          catch (\Exception $exception) {
            $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
            DCUError::logException($logger, $exception);
          }
          break;

        case 'secondary-yellow':
          // secondary-yellow to yellow.
          $paragraph->setBehaviorSettings('paragraph_colour', ['paragraph_color' => 'yellow']);
          try {
            $paragraph->save();
            $sandbox['debug_log'][$item_id] = 'Set colour from ' . $colour . ' to yellow';
          }
          catch (\Exception $exception) {
            $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
            DCUError::logException($logger, $exception);
          }
          break;

        case 'wa-white':
          // wa-white to blue.
          $paragraph->setBehaviorSettings('paragraph_colour', ['paragraph_color' => 'blue']);
          try {
            $paragraph->save();
            $sandbox['debug_log'][$item_id] = 'Set colour from ' . $colour . ' to blue';
          }
          catch (\Exception $exception) {
            $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
            DCUError::logException($logger, $exception);
          }
          break;

        default:
          $sandbox['debug_log'][$item_id] = $colour . ' was not changed';
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
