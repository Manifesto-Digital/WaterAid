<?php

/**
 * @file
 * Token integration for WaterAid Language module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\Entity\GroupRelationshipInterface;

/**
 * Implements hook_token_info().
 */
function wateraid_language_token_info(): array {
  $info = [];

  // Add token for group_relationship entities.
  $info['tokens']['group_relationship']['combined-prefix'] = [
    'name' => t('Combined group and language prefix'),
    'description' => t('The combined URL prefix based on group and language (e.g., "jp", "in", "in/hi").'),
  ];

  // Add token for group entities.
  $info['tokens']['group']['url-prefix'] = [
    'name' => t('Group URL prefix'),
    'description' => t('The URL prefix for the group based on site-language mapping.'),
  ];

  // Add token for node entities.
  $info['tokens']['node']['group-slug'] = [
    'name' => t('Group slug'),
    'description' => t('The URL prefix for the node based on its group membership (e.g., "jp", "in", "uk").'),
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function wateraid_language_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  $replacements = [];

  /** @var \Drupal\wateraid_language\SiteLanguageMapper $mapper */
  $mapper = \Drupal::service('wateraid_language.site_language_mapper');

  if ($type === 'group_relationship' && !empty($data['group_relationship'])) {
    /** @var \Drupal\group\Entity\GroupRelationshipInterface $group_relationship */
    $group_relationship = $data['group_relationship'];

    foreach ($tokens as $name => $original) {
      if ($name === 'combined-prefix') {
        $group = $group_relationship->getGroup();
        $entity = $group_relationship->getEntity();

        // Get the language of the related entity.
        $langcode = 'en';
        if ($entity && $entity->hasField('langcode')) {
          $langcode = $entity->language()->getId();
        }

        // Get the custom prefix from our mapping.
        $prefix = $mapper->getPrefix($group, $langcode);

        if ($prefix) {
          $replacements[$original] = $prefix;
        }
        else {
          // Fallback to group title if no mapping found.
          $replacements[$original] = $group->label();
        }
      }
    }
  }

  if ($type === 'group' && !empty($data['group'])) {
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $data['group'];

    foreach ($tokens as $name => $original) {
      if ($name === 'url-prefix') {
        // For group canonical URLs, use the language from the current context.
        $language_manager = \Drupal::languageManager();
        $langcode = $language_manager->getCurrentLanguage()->getId();

        // Get the custom prefix from our mapping.
        $prefix = $mapper->getPrefix($group, $langcode);

        if ($prefix) {
          $replacements[$original] = $prefix;
        }
        else {
          // Fallback to group title if no mapping found.
          $replacements[$original] = $group->label();
        }
      }
    }
  }

  if ($type === 'node' && !empty($data['node'])) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      if ($name === 'group-slug') {
        // Load the group relationship for this node.
        $relationships = GroupRelationship::loadByEntity($node);

        if (!empty($relationships)) {
          // Get the first group relationship (nodes should only be in one group).
          $relationship = reset($relationships);
          $group = $relationship->getGroup();

          // Get the slug directly from the group's field_slug field.
          if ($group->hasField('field_slug')) {
            $slug = $group->get('field_slug')->value;
            if ($slug) {
              // Remove leading slash if present.
              $replacements[$original] = ltrim($slug, '/');
              // Add cache tags for the group.
              $bubbleable_metadata->addCacheTags($group->getCacheTags());
            }
            else {
              // No slug value - return empty string.
              $replacements[$original] = '';
            }
          }
          else {
            // Fallback to group title if field_slug is not available.
            $replacements[$original] = $group->label();
            $bubbleable_metadata->addCacheTags($group->getCacheTags());
          }
        }
        else {
          // Node is not in a group - return empty string.
          $replacements[$original] = '';
        }
      }
    }
  }

  return $replacements;
}
