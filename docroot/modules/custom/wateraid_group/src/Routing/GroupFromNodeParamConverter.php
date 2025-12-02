<?php

declare(strict_types=1);

namespace Drupal\wateraid_group\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter that provides group context for node routes.
 *
 * This converter automatically adds the group parameter to node canonical
 * routes by looking up the node's group relationship. This allows all
 * downstream code that expects $params->get('group') to work transparently.
 */
final class GroupFromNodeParamConverter implements ParamConverterInterface {

  /**
   * Constructs a GroupFromNodeParamConverter.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    // The 'group' parameter has no value in the URL, so we derive it from
    // the node parameter (which is still just the node ID at this point).
    if (empty($defaults['node'])) {
      return FALSE;
    }

    $node_id = $defaults['node'];

    // Query for group relationships that reference this node.
    $relationship_storage = $this->entityTypeManager->getStorage('group_relationship');
    $relationships = $relationship_storage->loadByProperties([
      'entity_id' => $node_id,
    ]);

    if (empty($relationships)) {
      // No group relationship - return FALSE so parameter stays unset.
      return FALSE;
    }

    // Get the first relationship (nodes should only be in one group).
    $relationship = reset($relationships);
    return $relationship->getGroup();
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    // Only apply to our custom 'group_from_node' converter type.
    return !empty($definition['type']) && $definition['type'] === 'group_from_node';
  }

}


