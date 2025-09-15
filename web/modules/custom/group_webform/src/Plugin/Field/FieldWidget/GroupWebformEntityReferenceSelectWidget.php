<?php

namespace Drupal\group_webform\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\webform\Plugin\Field\FieldWidget\WebformEntityReferenceSelectWidget;

/**
 * Plugin for the 'group_webform_entity_reference_select' widget.
 *
 * @FieldWidget(
 *   id = "group_webform_entity_reference_select",
 *   label = @Translation("Group Webform Select List"),
 *   description = @Translation("A webform entity reference select field, showing only the group webforms to which the user has permissions to access."),
 *   field_types = {
 *     "webform"
 *   }
 * )
 *
 * // See https://git.drupalcode.org/project/webform_entity_reference_exclude
 * // And https://git.drupalcode.org/project/groupmenu
 */
class GroupWebformEntityReferenceSelectWidget extends WebformEntityReferenceSelectWidget {

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    // Get all available webforms.
    $options = parent::getOptions($entity);
    if (empty($options)) {
      return $options;
    }
    $account = \Drupal::service('groupwebform.webform')->getUser();
    $config = \Drupal::service('groupwebform.webform')->getConfig();
    $show_all_webforms = $config->get('show_all_webforms_in_gwselect') ?: FALSE;
    // If we allow all webforms in the Select list,
    // And user has webform admin permissions, return full list of options.
    if ($show_all_webforms && $account->hasPermission('administer webform')) {
      return $options;
    }
    // Remove all but user-accessible, group-related webforms from options list.
    $allowed_webform_ids = \Drupal::service('groupwebform.webform')->loadUserGroupWebformList();

    foreach ($options as $key => $option) {
      if (!in_array($key, $allowed_webform_ids)) {
        unset($options[$key]);
      }
    }

    return $options;
  }

}
