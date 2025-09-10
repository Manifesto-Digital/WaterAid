<?php

/**
 * @file
 * General WaterAid updates.
 */

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Site\Settings;
use Drupal\Core\Utility\Error as DCUError;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Post-update hook to set default Add to Head role visibility.
 */
function wateraid_base_core_post_update_set_default_add_to_head_role_visibility(): void {
  /** @var \Drupal\Core\Config\ImmutableConfig $settings */
  $settings = \Drupal::config('add_to_head.settings');

  if ($settings) {
    $profiles = $settings->get('add_to_head_profiles');
    foreach ($profiles as $key => $profile) {
      if ($profile['roles']['visibility'] === 0) {
        $profiles[$key]['roles']['visibility'] = 'exclude';
      }
    }

    \Drupal::configFactory()->getEditable('add_to_head.settings')
      ->set('add_to_head_profiles', $profiles)
      ->save();
  }

}

/**
 * Post-update hook to delete "topic-tag-text-colour-fix" add to head profile.
 */
function wateraid_base_core_post_update_delete_add_to_head_topic_profiles(): void {
  /** @var \Drupal\Core\Config\ImmutableConfig $settings */
  $settings = \Drupal::configFactory()->getEditable('add_to_head.settings');

  if ($settings) {
    $profiles = $settings->get('add_to_head_profiles');
    if (is_array($profiles)) {
      if (array_key_exists('topic-tag-text-colour-fix', $profiles)) {
        unset($profiles['topic-tag-text-colour-fix']);
      }
      $settings->set('add_to_head_profiles', $profiles)
        ->save();
    }
  }

}

/**
 * Update vertical alignment on hero paragraphs from 'none' to 'center'.
 */
function wateraid_base_core_post_update_hero_default_vertical_alignment(&$sandbox): mixed {
  if (!isset($sandbox['total'])) {
    $items = \Drupal::entityQuery('paragraph')
      ->condition('type', 'hero')
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

    if (empty($paragraph->get('field_align_vertical')->getValue())) {
      $paragraph->set('field_align_vertical', 'center');
      try {
        $paragraph->save();
        $sandbox['debug_log'][$item_id] = 'Default set';
      }
      catch (\Exception $exception) {
        $sandbox['debug_log'][$item_id] = 'Failed to set default';
        $logger = \Drupal::logger('Update');
        DCUError::logException($logger, $exception);
      }
    }
    else {
      $sandbox['debug_log'][$item_id] = 'Skipped - not null';
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
 * Update listing style on listing paragraphs from 'none' to 'no-background'.
 */
function wateraid_base_core_post_update_listing_default_listing_style(&$sandbox): mixed {
  if (!isset($sandbox['total'])) {
    $items = \Drupal::entityQuery('paragraph')
      ->condition('type', 'listing')
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

    if (empty($paragraph->get('field_listing_style')->getValue())) {
      $paragraph->set('field_listing_style', 'no-background');
      try {
        $paragraph->save();
        $sandbox['debug_log'][$item_id] = 'Default set';
      }
      catch (\Exception $exception) {
        $sandbox['debug_log'][$item_id] = 'Failed to set default';
        $logger = \Drupal::logger('Update');
        DCUError::logException($logger, $exception);
      }
    }
    else {
      $sandbox['debug_log'][$item_id] = 'Skipped - not null';
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
 * Update field type on testimonials custom block to allow formatting.
 */
function wateraid_base_core_post_update_change_field_type_testimonials_custom_block(): void {
  // Get the database connection.
  $database = Database::getConnection();
  // The entity type that the field is attached to.
  $entity_type = 'block_content';
  // The name of the field that we are updating.
  $field_name = 'field_testimonial_quote';
  // The name of the table that the field data is stored in.
  $table = $entity_type . '__' . $field_name;
  // Variable to store the current data in the field.
  $current_rows = NULL;
  // Variable to store the new fields that we will create.
  $new_fields_list = [];
  // Load the field storage config for the field that we are updating.
  $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);

  if (is_null($field_storage)) {
    return;
  }

  // Get all current data from the field table in the DB.
  if ($database->schema()->tableExists($table)) {
    $current_rows = $database->select($table, 'n')
      ->fields('n')
      ->execute()
      ->fetchAll();
  }

  // Use existing field config for new field.
  foreach ($field_storage->getBundles() as $bundle => $label) {
    // Load the field config for the current bundle.
    $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
    $new_field = $field->toArray();
    // Update the field type to string_long.
    $new_field['field_type'] = 'string_long';
    // Clear the settings for the new field.
    $new_field['settings'] = [];
    $new_fields_list[] = $new_field;
  }

  // Deleting field storage which will also delete bundles(fields).
  $new_field_storage = $field_storage->toArray();
  $new_field_storage['type'] = 'string_long';
  $new_field_storage['settings'] = [];
  $field_storage->delete();

  // Create new field storage.
  $new_field_storage = FieldStorageConfig::create($new_field_storage);
  $new_field_storage->save();

  // Create new fields.
  foreach ($new_fields_list as $nfield) {
    // Create a new field config using the data from the newFieldsList array.
    $nfield_config = FieldConfig::create($nfield);
    // Save the new field config.
    $nfield_config->save();
  }

  // Restore existing data in new table.
  if (!is_null($current_rows)) {
    // Iterate through each row of data.
    foreach ($current_rows as $row) {
      // Insert the data into the new table.
      $database->insert($table)
        ->fields((array) $row)
        ->execute();
    }
  }
}

/**
 * Post-update hook to update webform settings by changing emsail HTML editor.
 */
function wateraid_base_core_post_update_change_webform_email_editor(): void {
  /** @var \Drupal\Core\Config\ImmutableConfig $settings */
  $settings = \Drupal::configFactory()->getEditable('webform.settings');

  if ($settings) {
    $editor = $settings->get('html_editor');
    $editor['mail_format'] = 'wateraid_mail';
    $settings->set('html_editor', $editor)
      ->save();
  }
}

/**
 * Callback for disabling Asset Injector scripts.
 *
 * @param string $id
 *   Machine name of the script to disable.
 * @param string $type
 *   The Injector type (css or js).
 *
 * @return bool
 *   TRUE if the script was successfully disabled, otherwise FALSE.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function _wateraid_base_core_disable_asset_injector(string $id, string $type): bool {
  if (strtolower($type) == 'css') {
    /** @var \Drupal\asset_injector\Entity\AssetInjectorBase $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('asset_injector_css');
  }
  elseif (strtolower($type) == 'js') {
    /** @var \Drupal\asset_injector\Entity\AssetInjectorBase $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('asset_injector_js');
  }
  else {
    return FALSE;
  }

  $injector = $storage->load($id);

  if ($injector) {
    $injector->disable();
    $injector->save();
    return TRUE;
  }

  return FALSE;
}

/**
 * Disable "donation_featured_image_hide_image_mobile" Asset Injector script.
 */
function wateraid_base_core_post_update_disable_donation_image_asset_css() {
  $success = FALSE;
  try {
    $success = _wateraid_base_core_disable_asset_injector('donation_featured_image_hide_image_mobile', 'css');
  }
  catch (\Exception $e) {
    // Do nothing.
    return 'An exception occurred when disabling the Asset Injector script';
  }

  if ($success) {
    return 'Successfully disabled Asset Injector script';
  }
  else {
    return 'Failed to disable Asset Injector script';
  }
}

/**
 * Ensure field_colour_selector field is installed and update value to default.
 */
function wateraid_base_core_post_update_ensure_colour_field_exists_and_update_value(&$sandbox): mixed {
  $config_path = Settings::get('config_sync_directory');
  $config_manager = Drupal::service('config.manager');
  $source = new FileStorage($config_path);

  $entity_type_manager = Drupal::entityTypeManager();
  $field_storage = $entity_type_manager->getStorage('field_storage_config');
  $field_config = $entity_type_manager->getStorage('field_config');

  // Only try to create the field if it doesn't already exist.
  if (!$field_storage->load('paragraph.field_colour_selector')) {
    $config_record = $source->read('field.storage.paragraph.field_colour_selector');
    $entity_type = $config_manager->getEntityTypeIdByName('field.storage.paragraph.field_colour_selector');

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
    $storage = $entity_type_manager->getStorage($entity_type);

    // Create the config entity.
    $entity = $storage->createFromStorageRecord($config_record);
    $entity->save();
  }

  if (!$field_config->load('paragraph.donation_lp_text_with_subtext.field_colour_selector')) {
    $config_record = $source->read('field.field.paragraph.donation_lp_text_with_subtext.field_colour_selector');
    $entity_type = $config_manager->getEntityTypeIdByName('field.field.paragraph.donation_lp_text_with_subtext.field_colour_selector');

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
    $storage = $entity_type_manager->getStorage($entity_type);

    // Create the config entity.
    $entity = $storage->createFromStorageRecord($config_record);
    $entity->save();
  }

  // Update background colour on DLP Text with Subtext paragraphs
  // from 'none' to 'secondary-navy-green'.
  if (!isset($sandbox['total'])) {
    $items = \Drupal::entityQuery('paragraph')
      ->condition('type', 'donation_lp_text_with_subtext')
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

    if (empty($paragraph->get('field_colour_selector')->getValue())) {
      $paragraph->set('field_colour_selector', 'secondary-navy-green');
      try {
        $paragraph->save();
        $sandbox['debug_log'][$item_id] = 'Default set';
      }
      catch (\Exception $exception) {
        $sandbox['debug_log'][$item_id] = 'Failed to set default';
        $logger = \Drupal::logger('Update');
        DCUError::logException($logger, $exception);
      }
    }
    else {
      $sandbox['debug_log'][$item_id] = 'Skipped - not null';
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
 * Remove tertiary colours from pargraphs.
 */
function wateraid_base_core_post_update_paragraph_colours(&$sandbox): mixed {
  // Switch tertiary for appropriate secondary colours before theyre removed.
  if (!isset($sandbox['total'])) {
    $items = \Drupal::entityQuery('paragraph')
      ->condition('type', [
        'aside_with_image',
        'aside_with_text',
        'donation_lp_text_with_subtext',
        'hero',
        'listing_item',
        'page_section',
        'statistic',
      ], 'IN')
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

    _switch_tertiary_colour_options($paragraph, 'field_colour_selector', $item_id, $sandbox);

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
 * Remove tertiary colours from nodes.
 */
function wateraid_base_core_post_update_node_tertiary_colours(&$sandbox): mixed {
  // Switch tertiary for appropriate secondary colours before theyre removed.
  if (!isset($sandbox['total'])) {
    $items = \Drupal::entityQuery('node')
      ->condition('type', ['event', 'get_involved'], 'IN')
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
    /** @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($item_id);

    if ($node->hasField('field_event_info_bg_colour')) {
      _switch_tertiary_colour_options($node, 'field_event_info_bg_colour', $item_id, $sandbox);
    }
    if ($node->hasField('field_synopsis_background_colour')) {
      _switch_tertiary_colour_options($node, 'field_synopsis_background_colour', $item_id, $sandbox);
    }
    if ($node->hasField('field_colour_selector')) {
      _switch_tertiary_colour_options($node, 'field_colour_selector', $item_id, $sandbox);
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
 * Remove tertiary colours from blocks.
 */
function wateraid_base_core_post_update_block_tertiary_colours(&$sandbox): mixed {
  // Switch tertiary for appropriate secondary colours before theyre removed.
  if (!isset($sandbox['total'])) {
    $items = \Drupal::entityQuery('block_content')
      ->condition('type', 'aside_block')
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
    /** @var \Drupal\block\Entity\Block $block */
    $block = \Drupal::entityTypeManager()->getStorage('block_content')->load($item_id);

    _switch_tertiary_colour_options($block, 'field_border_colour', $item_id, $sandbox);
    _switch_tertiary_colour_options($block, 'field_background_colour', $item_id, $sandbox);

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
 * Switch the tertiary for secondary colours for the given field of an entity.
 *
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   Entity to update.
 * @param string $field_name
 *   Field that contains the colour selector.
 * @param string $item_id
 *   Entity ID for log.
 * @param array $sandbox
 *   Sandbox.
 */
function _switch_tertiary_colour_options(EntityInterface &$entity, string $field_name, string $item_id, array &$sandbox): void {
  if ($entity->get($field_name)->value) {
    $colour = $entity->get($field_name)->value;
    $logger = \Drupal::logger('Update');

    if ($colour === 'tertiary-light-brown') {
      // tertiary-light-brown to secondary-brown.
      $entity->set($field_name, 'secondary-brown');
      try {
        $entity->save();
        $sandbox['debug_log'][$item_id] = $sandbox['debug_log'][$item_id] . 'Set new colour to secondary-brown,';
      }
      catch (\Exception $exception) {
        $sandbox['debug_log'][$item_id] = $sandbox['debug_log'][$item_id] . 'Failed to set ' . $field_name . ' colour,';
        DCUError::logException($logger, $exception);
      }
    }
    elseif ($colour === 'deep-cyan') {
      // deep-cyan to secondary-navy-green.
      $entity->set($field_name, 'secondary-navy-green');
      try {
        $entity->save();
        $sandbox['debug_log'][$item_id] = $sandbox['debug_log'][$item_id] . 'Set new colour to secondary-navy-green,';
      }
      catch (\Exception $exception) {
        $sandbox['debug_log'][$item_id] = $sandbox['debug_log'][$item_id] . 'Failed to set ' . $field_name . ' colour,';
        DCUError::logException($logger, $exception);
      }
    }
    elseif ($colour === 'deep-purple') {
      // deep-purple to secondary-purple'.
      $entity->set($field_name, 'secondary-purple');
      try {
        $entity->save();
        $sandbox['debug_log'][$item_id] = $sandbox['debug_log'][$item_id] . 'Set new colour secondary-purple,';
      }
      catch (\Exception $exception) {
        $sandbox['debug_log'][$item_id] = $sandbox['debug_log'][$item_id] . 'Failed to set ' . $field_name . ' colour,';
        DCUError::logException($logger, $exception);
      }
    }
    else {
      $sandbox['debug_log'][$item_id] = 'Skipped - not tertiary colour';
    }
  }
}

/**
 * Run entity/field definition updates for the Drupal 10 upgrade.
 */
function wateraid_base_core_post_update_d10_entity_updates() {
  // Updates the entity definitions/fields.
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_type_manager->clearCachedDefinitions();

  $entity_type_ids = [];
  $change_summary = \Drupal::service('entity.definition_update_manager')->getChangeSummary();

  // Runs through each entity and updates.
  foreach ($change_summary as $entity_type_id => $change_list) {
    $entity_type = $entity_type_manager->getDefinition($entity_type_id);
    \Drupal::entityDefinitionUpdateManager()->installEntityType($entity_type);
    $entity_type_ids[] = $entity_type_id;
  }

  return t("Updated the entity types: @entity_type_ids", [
    '@entity_type_ids' => implode(', ', $entity_type_ids),
  ]);
}

/**
 * Adds a project term to Page Type vocabulary.
 */
function wateraid_base_core_post_update_add_project_term(): void {

  // Load the entity type manager service object.
  $entity_type_manager = \Drupal::entityTypeManager();
  /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $vocabulary_storage */
  $vocabulary_storage = $entity_type_manager->getStorage('taxonomy_vocabulary');
  $term_storage = $entity_type_manager->getStorage('taxonomy_term');

  $config_storage = \Drupal::service('config.storage.sync');
  $data = $config_storage->read('taxonomy.vocabulary.page_type');

  // Get the VID from config.
  $vid = $data['vid'];
  // Check if the vocabulary already exists within the system.
  $vocabulary = $vocabulary_storage->load($vid);

  if (!$vocabulary) {
    $vocabulary_storage->create($data)->save();
  }

  $term_storage->create([
    'name' => 'Project',
    'parent' => [],
    'vid' => $vid,
  ])->save();
}

/**
 * Update Hero light field for country details and press-media bios.
 */
function wateraid_base_core_post_update_hero_light_header_field(&$sandbox): mixed {
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
    $parent = $paragraph->getParentEntity();
    $parent_type = '';
    if ($parent) {
      $parent_type = $parent->bundle();
    }

    if ($parent_type === 'press_and_media_flexible' || $parent_type === 'country_details') {
      if ($paragraph->get('field_light_header')->getValue()) {
        $paragraph->set('field_light_header', 0);
        try {
          $paragraph->save();
          $sandbox['debug_log'][$item_id] = 'Set to false';
        }
        catch (\Exception $exception) {
          $sandbox['debug_log'][$item_id] = 'Failed to set value';
          $logger = \Drupal::logger('Update');
          DCUError::logException($logger, $exception);
        }
      }
      else {
        $sandbox['debug_log'][$item_id] = 'Skipped - no field';
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
 * Install rabbit hole entity/field definition updates.
 */
function wateraid_base_core_post_update_rabbit_hole_entity_updates() {
  $rh_definitions = \Drupal::service('plugin.manager.rabbit_hole_entity_plugin')->getDefinitions();
  $update_manager = \Drupal::entityDefinitionUpdateManager();

  $fields['rh_redirect'] = BaseFieldDefinition::create('string')
    ->setName('rh_redirect')
    ->setLabel(t('Rabbit Hole redirect path.'))
    ->setDescription(t('The path to where the user should get redirected to.'))
    ->setTranslatable(TRUE);
  $fields['rh_redirect_response'] = BaseFieldDefinition::create('integer')
    ->setName('rh_redirect_response')
    ->setLabel(t('Rabbit Hole redirect response code'))
    ->setDescription(t('Specifies the HTTP response code that should be used when perform a redirect.'))
    ->setTranslatable(TRUE);
  $fields['rh_redirect_fallback_action'] = BaseFieldDefinition::create('string')
    ->setName('rh_redirect_fallback_action')
    ->setLabel(t('Rabbit Hole redirect fallback action'))
    ->setDescription(t('Specifies the action that should be used when the redirect path is invalid or empty.'))
    ->setTranslatable(TRUE);

  $fields['rh_action'] = BaseFieldDefinition::create('string')
    ->setName('rh_action')
    ->setLabel(t('Rabbit Hole action'))
    ->setDescription(t('Specifies which action that Rabbit Hole should take.'))
    ->setTranslatable(TRUE);

  foreach ($rh_definitions as $rh_module => $definition) {
    $entity_type_id = $definition['entityType'];
    foreach ($fields as $key => $new_field_definition) {
      $field_storage_definition = $update_manager->getFieldStorageDefinition($key, $entity_type_id);
      if (empty($field_storage_definition)) {
        $update_manager->installFieldStorageDefinition($key, $entity_type_id, $rh_module, $new_field_definition);
      }
    }
  }
}

/**
 * Set paragraph colour fields to use brand refresh colours.
 */
function wateraid_base_core_post_update_paragraph_brand_refresh(&$sandbox): mixed {
  if (!isset($sandbox['total'])) {
    $items = \Drupal::entityQuery('paragraph')
      ->condition('type', [
        'aside_with_image',
        'aside_with_text',
        'donation_lp_text_with_subtext',
        'listing_item',
        'page_section',
        'statistic',
      ], 'IN')
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

    _switch_colours_brand_refresh($paragraph, 'field_colour_selector', $item_id, $sandbox);

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
 * Use brand refesh colours in colour selectors for nodes.
 */
function wateraid_base_core_post_update_node_brand_refresh(&$sandbox): mixed {
  if (!isset($sandbox['total'])) {
    $items = \Drupal::entityQuery('node')
      ->condition('type', ['event', 'get_involved'], 'IN')
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
    /** @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($item_id);

    if ($node->hasField('field_event_info_bg_colour')) {
      _switch_colours_brand_refresh($node, 'field_event_info_bg_colour', $item_id, $sandbox);
    }
    if ($node->hasField('field_synopsis_background_colour')) {
      _switch_colours_brand_refresh($node, 'field_synopsis_background_colour', $item_id, $sandbox);
    }
    if ($node->hasField('field_colour_selector')) {
      _switch_colours_brand_refresh($node, 'field_colour_selector', $item_id, $sandbox);
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
 * Use brand refresh colours in colour selectors.
 *
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   Entity to update.
 * @param string $field_name
 *   Field that contains the colour selector.
 * @param string $item_id
 *   Entity ID for log.
 * @param array $sandbox
 *   Sandbox.
 */
function _switch_colours_brand_refresh(EntityInterface &$entity, string $field_name, string $item_id, array &$sandbox): void {
  if ($colour = $entity->get($field_name)->value) {
    $logger = \Drupal::logger('Update');

    switch ($colour) {
      case 'wa-black':
        // wa-black to navy.
        $entity->set($field_name, 'navy');
        try {
          $entity->save();
          $sandbox['debug_log'][$item_id] = 'Set colour from ' . $colour . ' to navy';
        }
        catch (\Exception $exception) {
          $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
          DCUError::logException($logger, $exception);
        }
        break;

      case 'wa-blue':
      case 'secondary-blue':
        // wa-blue and secondary-blue to blue.
        $entity->set($field_name, 'blue');
        try {
          $entity->save();
          $sandbox['debug_log'][$item_id] = 'Set colour from ' . $colour . ' to blue';
        }
        catch (\Exception $exception) {
          $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
          DCUError::logException($logger, $exception);
        }
        break;

      case 'secondary-orange':
        // secondary-orange to yellow.
        $entity->set($field_name, 'yellow');
        try {
          $entity->save();
          $sandbox['debug_log'][$item_id] = 'Set colour from secondary-orange to yellow';
        }
        catch (\Exception $exception) {
          $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
          DCUError::logException($logger, $exception);
        }
        break;

      case 'secondary-light-green':
        // secondary-light-green to light-green.
        $entity->set($field_name, 'light-green');
        try {
          $entity->save();
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
        $entity->set($field_name, 'dark-green');
        try {
          $entity->save();
          $sandbox['debug_log'][$item_id] = 'Set colour from ' . $colour . ' to dark-green';
        }
        catch (\Exception $exception) {
          $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
          DCUError::logException($logger, $exception);
        }
        break;

      case 'secondary-fuscia':
        // secondary-fuscia to pink.
        $entity->set($field_name, 'pink');
        try {
          $entity->save();
          $sandbox['debug_log'][$item_id] = 'Set colour from secondary-fuscia to pink';
        }
        catch (\Exception $exception) {
          $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
          DCUError::logException($logger, $exception);
        }
        break;

      case 'secondary-purple':
        // secondary-purple to plum.
        $entity->set($field_name, 'plum');
        try {
          $entity->save();
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
        $entity->set($field_name, 'orange');
        try {
          $entity->save();
          $sandbox['debug_log'][$item_id] = 'Set colour from ' . $colour . ' to orange';
        }
        catch (\Exception $exception) {
          $sandbox['debug_log'][$item_id] = 'Failed to set new colour for ' . $colour;
          DCUError::logException($logger, $exception);
        }
        break;

      default:
        $sandbox['debug_log'][$item_id] = $colour . ' was not changed in ' . $field_name;
    }
  }
}
