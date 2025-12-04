<?php

/**
 * @file
 *  Deploy hooks for the Wateraid Donation Forms.
 */

/**
 * Sets the donation reminder to display by default on the Uk group.
 */
function wateraid_donation_forms_deploy_add_donation_reminder(array &$sandbox): void {

  /** @var \Drupal\group\Entity\GroupInterface $group */
  foreach (\Drupal::entityTypeManager()->getStorage('group')->loadByProperties([
    'label' => 'WaterAid UK',
  ]) as $group) {
    $group->set('field_show_donation_reminder', TRUE);
    $group->save();
  }
}
