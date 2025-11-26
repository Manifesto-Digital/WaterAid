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

/**
 * Update the UK Group with new values.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function group_webform_deploy_update_uk_group(): void {
  if ($groups = \Drupal::entityTypeManager()->getStorage('group')->loadByProperties([
    'label' => 'WaterAid UK',
  ])) {
    $group = reset($groups);

    $group->set('field_group_language', 'en');
    $group->save();
  }
}

/**
 * Create the Sweden and Hindi groups.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function group_webform_deploy_create_new_groups(): void {
  $storage = \Drupal::entityTypeManager()->getStorage('group');

  /** @var \Drupal\group\Entity\GroupInterface $sweden */
  $sweden = $storage->create([
    'label' => 'WaterAid Sweden',
    'field_group_language' => 'sv',
    'language' => 'sv',
    'type' => 'wateraid_site',
    'uid' => 1,
  ]);
  $sweden->save();

  /** @var \Drupal\group\Entity\GroupInterface $india */
  $india = $storage->create([
    'label' => 'WaterAid India',
    'field_group_language' => 'en',
    'language' => 'en',
    'type' => 'wateraid_site',
    'uid' => 1,
  ]);
  $india->save();

  $hindi = $india->addTranslation('hi');
  $hindi->set('label', 'Wateraid India (Hindi)');
  $hindi->save();
}
