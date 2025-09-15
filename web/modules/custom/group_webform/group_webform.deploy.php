<?php

/**
 * @file
 * Deploy hooks for the Group Webform module.
 */

/**
 * Helper to create the default group.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function group_webform_deploy_add_uk_group(): void {
  $storage = \Drupal::entityTypeManager()->getStorage('group');
  if (!$storage->loadByProperties([
    'label' => 'WaterAid UK',
  ])) {
    try {
      $group = $storage->create([
        'label' => 'WaterAid UK',
        'type' => 'wateraid_site',
        'uid' => 1,
      ]);

      $group->save();
    }
    catch (Exception $e) {
      \Drupal::logger('group_webform')->error(t('Error creating the default group: :error', [
        ':error' => $e->getMessage(),
      ]));
    }
  }
}
