<?php

/**
 * @file
 * Token integration for WaterAid Language module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\GroupRelationship;


/**
 * Implements hook_token_info().
 */
function wateraid_language_token_info(): array {
  $info = [];

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
