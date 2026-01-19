<?php

namespace Drupal\wateraid_group\Plugin\Linkit\Matcher;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\linkit\Plugin\Linkit\Matcher\NodeMatcher;
use Drupal\node\NodeInterface;

/**
 * Provides specific linkit matchers for the node entity type.
 *
 * @Matcher(
 *   id = "entity:node",
 *   label = @Translation("Group Content"),
 *   target_entity = "node",
 *   provider = "wateraid_group"
 * )
 */
class GroupNodeMatcher extends NodeMatcher {

  /**
   * Builds the label string used in the suggestion.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The matched entity.
   *
   * @return string
   *   The label for this entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildLabel(EntityInterface $entity): string {
    $label = $entity->label();

    if ($entity instanceof NodeInterface) {

      /** @var \Drupal\group\Entity\GroupRelationshipInterface $relationship */
      if ($relationships = $this->entityTypeManager->getStorage('group_relationship')
        ->loadByProperties([
          'entity_id' => $entity->id(),
          'plugin_id' => 'group_node:' . $entity->bundle(),
        ])) {

        $group_labels = [];
        foreach ($relationships as $relationship) {
          if ($group = $relationship->getGroup()) {
            $group_labels[] = ($group->hasField('field_slug')) ? $group->get('field_slug')
              ->getString() : $group->label();
          }
        }

        if ($group_labels) {
          $label = '(' . implode(', ', $group_labels) . ') ' . $label;
        }
      }
    }

    return Html::escape($label);
  }

}
