<?php

declare(strict_types=1);

namespace Drupal\wateraid_core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks if passed parameter matches the route configuration.
 *
 * Usage example:
 * @code
 * foo.example:
 *   path: '/example/{parameter}'
 *   defaults:
 *     _title: 'Example'
 *     _controller: '\Drupal\wateraid_core\Controller\WateraidCoreController'
 *   requirements:
 *     _node_access: 'some value'
 * @endcode
 */
final class NodeAccessAccessChecker implements AccessInterface {

  /**
   * Constructs a FooAccessChecker object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Access callback.
   */
  public function access($node, AccountInterface $account): AccessResult {
    $entity = $this->entityTypeManager->getStorage('node')->load($node);
    $relationships = GroupRelationship::loadByEntity($entity);

    if (empty($relationships)) {
      return AccessResult::neutral()->cachePerUser();
    }

    if ($account->hasPermission('access nodes directly')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    else {
      return AccessResult::forbidden()->cachePerPermissions();
    }
  }

}
