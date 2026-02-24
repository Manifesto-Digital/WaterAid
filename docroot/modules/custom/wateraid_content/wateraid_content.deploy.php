<?php

/**
 * @file
 *  Deploy hooks for the WaterAid Groups.
 */

/**
 * Move default donate widget settings to new field.
 */
function wateraid_content_deploy_update_settings(&$sandbox): void {
  if (!isset($sandbox['ids'])) {
    $sandbox['ids'] = \Drupal::entityTypeManager()->getStorage('paragraph')->getQuery()
      ->condition('type', 'donation_widget')
      ->accessCheck(FALSE)
      ->execute();
    $sandbox['max'] = count($sandbox['ids']);
    $sandbox['#finished'] = 0;
  }

  if ($id = array_pop($sandbox['ids'])) {
    if ($paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($id)) {
      $value = $paragraph->get('field_monthly_default')->getString() ? 'monthly' : 'one_off';
      $paragraph->set('field_default_option', $value);
      $paragraph->save();
    }
  }

  $sandbox['#finished'] = empty($sandbox['max']) || empty($sandbox['ids']) ? 1 : ($sandbox['max'] - count($sandbox['ids'])) / $sandbox['max'];
}
