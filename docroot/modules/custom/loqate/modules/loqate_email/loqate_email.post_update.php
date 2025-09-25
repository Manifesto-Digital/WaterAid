<?php

/**
 * @file
 * Update webform components.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Serialization\Yaml;

/**
 * Update the webform emails.
 *
 * @param mixed[] $sandbox
 *   Drupal sandbox array.
 *
 * @return string
 *   Progress message.
 */
function loqate_email_post_update_migrate_webform_fields(array &$sandbox): string {
  // Select all webforms containing an email field.
  $query = \Drupal::database()->select('config', 'c');
  $query->fields('c', ['name']);
  $query->condition('name', 'webform.webform.%', 'LIKE');
  $query->condition('data', "%'#type': email%", 'LIKE');
  $query->range(0, 1);
  $webforms_to_update = $query->execute()->fetchCol();

  $sandbox['#finished'] = (int) empty($webforms_to_update);

  foreach ($webforms_to_update as $webform_config_name) {
    $id = str_replace('webform.webform.', '', $webform_config_name);
    _loqate_email_post_update_migrate_webform_email_field_to_loqate_email($id);
    return 'Migrated webform: ' . $id;
  }

  return '';
}

/**
 * Helper function to migrate from email to Loqate email.
 *
 * @param string $webform_id
 *   The webform id.
 *
 * @return bool
 *   Success/failure.
 */
function _loqate_email_post_update_migrate_webform_email_field_to_loqate_email(string $webform_id): bool {
  $webform_config_name = 'webform.webform.' . $webform_id;
  $editable = \Drupal::configFactory()->getEditable($webform_config_name);
  $elements = $editable->get('elements');
  $elements = Yaml::decode($elements);

  $updated_elements = [];
  _loqate_email_post_update_update_loqate_email_children($elements, $updated_elements);

  if (!empty($updated_elements)) {
    // Update handlers.
    $handlers = $editable->get('handlers');
    _loqate_email_post_update_update_handlers($handlers, $updated_elements);

    foreach ($updated_elements as $updated_name) {
      $update = \Drupal::database()->update('webform_submission_data');
      $update->fields(['property' => 'email']);
      $update->condition('webform_id', $webform_id);
      $update->condition('name', $updated_name);
      $update->execute();
    }

    $editable->set('elements', Yaml::encode($elements));
    $editable->set('handlers', $handlers);
    $editable->save();
    return TRUE;
  }

  return FALSE;
}

/**
 * Helper function to recursively update webform elements.
 *
 * @param mixed[] $elements
 *   An array of elements.
 * @param mixed[] $updated
 *   Array of updated elements.
 * @param string $current_name
 *   The current name.
 */
function _loqate_email_post_update_update_loqate_email_children(array &$elements, array &$updated, string $current_name = ''): void {
  if (isset($elements['#type']) && $elements['#type'] == 'email') {
    // phpcs:disable
    $settings_map = [
      '#title' => '#email__title',
      '#placeholder' => '#email__placeholder',
      '#required' => '#email__required',
      '#description' => '#email__description',
    ];
    // phpcs:enable
    foreach ($elements as $element_name => $element_value) {
      $updated[$current_name] = $current_name;
      if (!str_starts_with($element_name, '#')) {
        continue;
      }

      // Default to the same setting name.
      $new_setting_name = $element_name;

      // If the name is in the map, use the mapped setting name.
      if (!empty($settings_map[$element_name])) {
        $new_setting_name = $settings_map[$element_name];
      }

      // Ensure the value is in the array.
      $elements[$new_setting_name] = $element_value;

    }
    $elements['#type'] = 'loqate_email_composite';

    if (empty($elements['#loqate_validation'])) {
      // The new element has Loqate enabled by default. On Email fields,
      // Loqate is disabled by default. If the old field was in its default
      // state, we now have to explicitly disable the option.
      $elements['#loqate_validation'] = FALSE;
    }
    else {
      unset($elements['#loqate_validation']);
    }
  }

  // Recursively check children for nested components.
  $element_names = Element::children($elements);
  foreach ($element_names as $element_name) {
    _loqate_email_post_update_update_loqate_email_children($elements[$element_name], $updated, $element_name);
  }
}

/**
 * Helper function for updating handlers.
 *
 * @param mixed[] $handlers
 *   The webform handlers.
 * @param mixed[] $updated_elements
 *   The updated elements.
 * @param string $current_name
 *   The current name.
 */
function _loqate_email_post_update_update_handlers(array &$handlers, array $updated_elements, string $current_name = ''): void {
  $replacement_values = [];
  foreach ($updated_elements as $element_name) {
    // Warning: As the "search" value is used in a string replacement operation,
    // ensure the values are unambiguous e.g. by include closing braces.
    // For example, searching "[webform_submission:values:test" would also
    // match "[webform_submission:values:test_unintended_field".
    $replacement_values[] = [
      'search' => '[webform_submission:values:' . $element_name . ']',
      'replace' => '[webform_submission:values:' . $element_name . ':email]',
    ];
    $replacement_values[] = [
      'search' => '[webform_submission:values:' . $element_name . ':raw]',
      'replace' => '[webform_submission:values:' . $element_name . ':email:raw]',
    ];
  }

  // Convert the handler from an array to serialised string so
  // string replacement can be carried out without needing to
  // traverse the hierarchical structure.
  $handler_yaml = Yaml::encode($handlers);
  foreach ($replacement_values as $replacements) {
    // Find and replace values.
    $handler_yaml = str_replace($replacements['search'], $replacements['replace'], $handler_yaml);
  }

  // Convert the serialised string back into an array.
  $handlers = Yaml::decode($handler_yaml);
}
