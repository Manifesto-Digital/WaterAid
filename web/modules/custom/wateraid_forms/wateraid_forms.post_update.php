<?php

/**
 * @file
 * Updates relating to forms in general.
 */

/**
 * Post-update hook to set all existing Webforms to styling v1.
 */
function wateraid_forms_post_update_styling_versions(&$sandbox): mixed {
  if (!isset($sandbox['total'])) {
    // Get a list of all Webform IDs.
    $items = \Drupal::entityQuery('webform')
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

  $batch_size = 10;
  $item_ids = array_slice($sandbox['items'], $sandbox['current'], $batch_size);
  if (empty($item_ids)) {
    $sandbox['#finished'] = 1;
  }

  // Initialise storage.
  $webform_storage = \Drupal::entityTypeManager()->getStorage('webform');

  foreach ($item_ids as $item_id) {
    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = $webform_storage->load($item_id);
    try {
      $webform->setThirdPartySetting('wateraid_forms', 'style_version', 'v1');
      $webform->save();
      $sandbox['debug_log'][] = 'Successfully set v1 as style_version on Webform ' . $item_id;
    }
    catch (\Exception $e) {
      $sandbox['debug_log'][] = 'Failed to save Webform ' . $item_id;
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
