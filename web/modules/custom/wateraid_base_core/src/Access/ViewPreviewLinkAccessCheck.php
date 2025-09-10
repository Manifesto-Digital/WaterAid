<?php

namespace Drupal\wateraid_base_core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\node\NodeInterface;

/**
 * Access checker for viewing preview links.
 */
class ViewPreviewLinkAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * PreviewLinkAccessCheck constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Properly check for valid preview tokens.
   *
   * @param \Drupal\node\NodeInterface|null $node
   *   The node being previewed.
   * @param string|null $preview_token
   *   The preview token provided from the URL.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether access should be granted or not.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(?NodeInterface $node = NULL, ?string $preview_token = NULL): AccessResultInterface {
    // If there is no node or token, deny access.
    if (empty($node) || empty($preview_token)) {
      return AccessResult::forbidden('No entity or preview link provided.');
    }

    // If the node doesn't have a preview link, deny access.
    /** @var \Drupal\preview_link\PreviewLinkStorageInterface $preview_link */
    $preview_storage = $this->entityTypeManager->getStorage('preview_link');
    /** @var \Drupal\preview_link\Entity\PreviewLinkInterface[] $preview_links */
    $preview_links = $preview_storage->loadByProperties([
      'entities' => $node->id(),
    ]);
    if (empty($preview_links)) {
      return AccessResult::forbidden("Entity doesn't have a preview link.");
    }

    // If the preview link token and the provided token are the same, allow
    // access.
    foreach ($preview_links as $preview_link) {
      if ($preview_link->getToken() === $preview_token) {
        return AccessResult::allowed()
          ->addCacheableDependency($preview_link);
      }
    }

    // Otherwise deny access.
    return AccessResult::forbidden('Invalid preview token.');
  }

}
