<?php

/**
 * @file
 * Update UK webform components to Loqate without requiring an extra deployment.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Serialization\Yaml;
use Drupal\loqate\PcaAddressFieldMapping\PcaAddressElement;
use Drupal\webform\Entity\Webform;

/**
 * Migrate Address webform components to Loqate.
 */
function wateraid_azure_storage_post_update_convert_webform_address_loqate(array &$sandbox): void {
  // Only update webforms on the UK site.
  $site = \Drupal::config('system.date')->get('country.default');
  if ($site != 'GB') {
    return;
  }

  $property_map = [
    'address' => PcaAddressElement::LINE1,
    'address_2' => PcaAddressElement::LINE2,
    'city' => PcaAddressElement::LOCALITY,
    'state_province' => PcaAddressElement::ADMINISTRATIVE_AREA,
    'country' => PcaAddressElement::COUNTRY_CODE,
    'postal_code' => PcaAddressElement::POSTAL_CODE,
  ];

  $query = \Drupal::database()->select('config', 'c');
  $query->fields('c', ['name']);
  $query->condition('name', 'webform.webform.%', 'LIKE');
  $query->condition('data', "%'#capture_plus_active': true%", 'LIKE');
  // Do one at a time to avoid time outs, using $sandbox to track whether the
  // process is complete.
  $query->range(0, 1);
  $webforms_to_update = $query->execute()->fetchCol();

  $sandbox['#finished'] = (int) empty($webforms_to_update);

  foreach ($webforms_to_update as $webform_config_name) {
    // Update Capture Plus elements to Loqate address elements for the current
    // webform.
    $editable = \Drupal::configFactory()->getEditable($webform_config_name);
    $elements = $editable->get('elements');
    $elements = Yaml::decode($elements);

    // Recursively update all address elements.
    $updated_address_names = [];
    _wateraid_azure_storage_update_address_children($elements, $updated_address_names);

    // Update saved values for this form if any fields were updated.
    if (!empty($updated_address_names)) {
      $webform_id = str_replace('webform.webform.', '', $webform_config_name);

      $webform = Webform::load($webform_id);
      $columns = $webform->getState('results.custom.columns');
      if (!empty($columns)) {
        foreach ($updated_address_names as $updated_address_name) {
          foreach ($property_map as $original_property => $new_property) {
            $column_id = array_search('element__' . $updated_address_name . '__' . $original_property, $columns);
            if ($column_id) {
              $columns[$column_id] = 'element__' . $updated_address_name . '__' . $new_property;
            }
          }
        }
        $webform->setState('results.custom.columns', $columns);
        $webform->save();
      }

      foreach ($property_map as $original_property => $new_property) {
        $replacement = ['property' => $new_property];

        $update = \Drupal::database()->update('webform_submission_data');
        $update->fields($replacement);
        $update->condition('webform_id', $webform_id);
        $update->condition('property', $original_property);
        $update->execute();
      }

      // Once all data is updated, save the new widget to config.
      $editable->set('elements', Yaml::encode($elements));
      $editable->save();
    }
  }
}

/**
 * Recursively search array of elements, updating address widgets.
 *
 * @param array $elements
 *   Array of elements to search.
 * @param array $updated_address_names
 *   Array of address element names that are updated.
 * @param string $current_name
 *   The current name.
 */
function _wateraid_azure_storage_update_address_children(array &$elements, array &$updated_address_names, string $current_name = ''): void {
  // Only update webform_address elements.
  if (isset($elements['#type']) && $elements['#type'] == 'webform_address') {
    $updated_address_names[$current_name] = $current_name;
    $settings_map = [
      '#address__title' => '#' . PcaAddressElement::LINE1 . '_label',
      '#address_2__title' => '#' . PcaAddressElement::LINE2 . '_label',
      '#city__title' => '#' . PcaAddressElement::LOCALITY . '_label',
      '#state_province__title' => '#' . PcaAddressElement::ADMINISTRATIVE_AREA . '_label',
      '#postal_code__title' => '#' . PcaAddressElement::POSTAL_CODE . '_label',
      '#country__title' => '#' . PcaAddressElement::COUNTRY_CODE . '_label',
    ];
    foreach ($elements as $element_name => $element_value) {
      // We're only interested in settings for the current level here.
      if (strpos($element_name, '#') !== 0) {
        continue;
      }

      // Do not keep the capture_plus_active setting otherwise the loop will
      // continue indefinitely.
      if ($element_name === '#capture_plus_active') {
        unset($elements[$element_name]);
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
      // Remove the old setting if the new setting name is different.
      if ($element_name != $new_setting_name) {
        unset($elements[$element_name]);
      }
    }

    // Update type and default to the live API key.
    $elements['#type'] = 'pca_address_php';
    $elements['#loqate_api_key'] = 'loqate_live_api_key';
    // All Capture Plus widgets acted as if required, regardless of the actual
    // setting in config. Bring this behaviour across to Loqate.
    $elements['#required'] = TRUE;
  }

  // Recursively check children for nested address components.
  $element_names = Element::children($elements);
  foreach ($element_names as $element_name) {
    _wateraid_azure_storage_update_address_children($elements[$element_name], $updated_address_names, $element_name);
  }
}
