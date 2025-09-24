<?php

declare(strict_types=1);

namespace Drupal\group_webform\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks if passed parameter matches the route configuration.
 */
final class GroupRemoveAccessChecker implements AccessInterface {

  /**
   * Constructs a GroupRemoveAccessChecker object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Access callback.
   */
  public function access(AccountInterface $account, int|null $node = NULL, $webform = NULL): AccessResult {
    if (!$node && !$webform) {

      // If we don't have any parameters, no-one should be able to access this.
      return AccessResult::forbidden();
    }

    if ($node) {
      $entity = $this->entityTypeManager->getStorage('node')->load($node);
    }
    else {
      $entity = $this->entityTypeManager->getStorage('webform')->load($webform);
    }

    if (!$entity) {

      // If we don't have a valid entity, again deny access.
      return AccessResult::forbidden();
    }

    /** @var \Drupal\group\Entity\Storage\GroupRelationshipStorageInterface $group_relationship_storage */
    $group_relationship_storage = $this->entityTypeManager->getStorage('group_relationship');
    $group_relationships = $group_relationship_storage->loadByEntity($entity);

    // If the content is not in a group, we can't remove it. If it is in more
    // than one group, we won't know which one to remove it from.
    if (!$group_relationships || count($group_relationships) > 1) {

      // Changes to the user may mean they changed role in a group; changes to
      // the group relationship list may mean this got added to or removed from
      // a group.
      return AccessResult::forbidden()->cachePerUser()->addCacheTags(['group_relationship_list']);
    }

    /** @var \Drupal\group\Entity\GroupRelationshipInterface $relationship */
    $relationship = reset($group_relationships);

    $group = $relationship->getGroup();
    $plugin_id = $relationship->getPluginId();

    // If the user can remove any content from a group, they have access.
    $permission = "delete any $plugin_id relationship";

    if ($group->hasPermission($permission, $account)) {

      // Changes to the user may mean they changed role in a group; changes to
      // the group relationship may mean this got removed from the group.
      return AccessResult::allowed()->cachePerUser()->addCacheableDependency($relationship);
    }

    // If they can remove their own content from a group AND they created this
    // content, then ditto.
    $permission = "delete own $plugin_id relationship";

    if ($entity->getOwnerId() == $account->id() && $group->hasPermission($permission, $account)) {
      // Changes to the user may mean they changed role in a group; changes to
      // the group relationship may mean this got removed from the group;
      // changes to the entity may mean the ownership changed.
      return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity)->addCacheableDependency($relationship);
    }

    // Changes to the user may mean they changed role in a group; changes to
    // the group relationship list may mean this got added to or removed from
    // the group; changes to the entity may mean the ownership changed.
    return AccessResult::forbidden()->cachePerUser()->addCacheableDependency($entity)->addCacheTags(['group_relationship_list']);
  }

}
