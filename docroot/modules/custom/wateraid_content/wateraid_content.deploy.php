<?php

/**
 * @file
 *  Deploy hooks for the WaterAid Groups.
 */

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

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

/**
 * Set the publication date.
 */
function wateraid_content_deploy_set_publication_date(&$sandbox): void {
  if (!isset($sandbox['ids'])) {
    $sandbox['ids'] = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->condition('type', 'story')
      ->accessCheck(FALSE)
      ->execute();
    $sandbox['max'] = count($sandbox['ids']);
    $sandbox['#finished'] = 0;
  }

  if ($id = array_pop($sandbox['ids'])) {

    /** @var \Drupal\node\NodeInterface $node */
    if ($node = \Drupal::entityTypeManager()->getStorage('node')->load($id)) {
      $created = DrupalDateTime::createFromTimestamp($node->getCreatedTime());
      $node->set('field_publication_date', $created->format(DateTimeItemInterface::DATE_STORAGE_FORMAT));
      $node->save();
    }
  }

  $sandbox['#finished'] = empty($sandbox['max']) || empty($sandbox['ids']) ? 1 : ($sandbox['max'] - count($sandbox['ids'])) / $sandbox['max'];
}
