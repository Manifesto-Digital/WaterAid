<?php

namespace Drupal\wateraid_base_core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Class GeneratePreviewLinkAccessCheck.
 *
 * Provides an access check for preview link generation.
 *
 * @package Drupal\wateraid_base_core\Access
 */
class GeneratePreviewLinkAccessCheck implements AccessInterface {

  /**
   * Access check for generating preview links.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account attempting to access the preview.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The AccessResult for whether access is granted.
   */
  public function access(AccountInterface $account, RouteMatchInterface $route_match): AccessResultInterface {
    if ($node = $route_match->getParameter('node')) {
      if ($node instanceof NodeInterface) {
        // Only allow users to generate preview links if they can edit the node.
        return AccessResult::allowedIf($node->access('update', $account))
          ->addCacheableDependency($node)
          ->addCacheContexts(['route']);
      }
    }
    return AccessResult::neutral();
  }

}
