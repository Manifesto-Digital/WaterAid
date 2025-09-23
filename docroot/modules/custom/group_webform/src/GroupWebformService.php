<?php

namespace Drupal\group_webform;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\GroupMembershipLoader;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Checks access for displaying webform pages.
 */
class GroupWebformService implements GroupWebformServiceInterface {

  /**
   * Static cache of groupwebform.settings.
   *
   * @var array
   */
  protected $groupWebformConfig;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user's account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoader
   */
  protected $membershipLoader;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * An array containing the webform access results.
   *
   * @var array
   */
  protected $webformAccess = [];

  /**
   * Array of user-accessible group-related webform IDs.
   *
   * @var array
   */
  protected $userWebformList = [];

  /**
   * Static cache of all group webform objects keyed by group ID.
   *
   * @var \Drupal\webform\WebformInterface[]
   */
  protected $groupWebforms = [];

  /**
   * Constructs a new GroupTypeController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\group\GroupMembershipLoader $membership_loader
   *   The group membership loader.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, GroupMembershipLoader $membership_loader, ConfigFactoryInterface $configFactory, RouteMatchInterface $current_route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->membershipLoader = $membership_loader;
    $this->configFactory = $configFactory;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    if (!$this->groupWebformConfig) {
      $this->groupWebformConfig = $this->configFactory->get('group_webform.settings');
    }
    return $this->groupWebformConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfig(string $key, string $value) {
    $this->configFactory->getEditable('group_webform.settings')->set($key, $value)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function webformAccess($op, WebformInterface $webform = NULL, AccountInterface $account = NULL) {
    // Empty checks prevent error when other processes
    // check a custom access route.
    if (empty($webform)) {
      return AccessResult::neutral();
    }

    if (!isset($account)) {
      $account = $this->currentUser;
    }

    $account_id = $account->id();
    $webform_id = $webform->id();
    $plugin_id = 'group_webform:webform';

    if (isset($this->webformAccess[$op][$account_id][$webform_id])) {
      return $this->webformAccess[$op][$account_id][$webform_id];
    }
    // If user has sitewide admin permissions, immediately allow access.
    if ($account->hasPermission('administer webform')) {
      return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::allowed();
    }

    // Load all group relationships for this webform.
    $group_relationship_storage = $this->entityTypeManager->getStorage('group_relationship');
    $group_relationships = $group_relationship_storage->loadByEntity($webform);

    // If the webform does not belong to any group, we have nothing to say.
    if (empty($group_relationships)) {
      // Unless it's the canonical route (entity.webform.canonical)
      // which should be allowed by default if the webform
      // doesn't belong to a group.
      $current_route_match = $this->currentRouteMatch->getRouteName();
      if ($current_route_match === 'entity.webform.canonical') {
        return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::allowed();
      }
      else {
        return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::neutral();
      }
    }

    /** @var \Drupal\group\Entity\GroupInterface[] $groups */
    $groups = [];
    $relationship_webforms = [];

    foreach ($group_relationships as $group_relationship) {
      /** @var \Drupal\group\Entity\GroupRelationshipInterface $group_relationship */
      $group = $group_relationship->getGroup();
      $group_id = $group->id();
      $groups[$group_id] = $group;
      $relationship_webforms[$group_id][$webform_id]['owner'] = $group_relationship->getOwner()->id();
    }

    // From this point on you need group to allow you to perform the requested
    // operation. If you are not granted access for a group, you should be
    // denied access instead.
    // This will automatically include subgroups
    // because subgroups inherit access permissions from the parent group.
    foreach ($groups as $group) {
      // Multiple group support:
      // If any of the webform's groups allow this user access,
      // grant access.
      if ($group->hasPermission("administer webform", $account)) {
        // If user has admin permissions within one of the groups,
        // immediately allow access.
        return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::allowed();
      }

      $group_id = $group->id();

      if ($group->hasPermission("$op any $plugin_id entity", $account)
      || $group->hasPermission("$op $plugin_id entity", $account)) {
        return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::allowed();
      }
      elseif (
        // If user is owner of webform, and has permission to
        // view own webform, grant access.
        $group->hasPermission("$op own $plugin_id entity", $account)
        && $relationship_webforms[$group_id][$webform_id]['owner'] == $account_id
      ) {
        return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::allowed();
      }
      else {
        // Add support for outsider roles.
        $role_storage = $this->entityTypeManager->getStorage('group_role');
        $roles = $role_storage->loadByUserAndGroup($account, $group);
        foreach ($roles as $role) {
          if ($role->getScope() === 'outsider'
          && ($role->hasPermission("$op any $plugin_id entity")
              || $role->hasPermission("$op own $plugin_id entity")
              || $role->hasPermission("$op $plugin_id entity"))) {
            return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::allowed();
          }
        }
      }
    }
    // Otherwise, deny access.
    return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function webformSubmissionAccessItems($op, WebformSubmissionInterface $webform_submission, AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }
    $account_id = $account->id();
    $webform = $webform_submission->getWebform();
    $webform_id = $webform->id();
    $plugin_id = 'group_webform:webform';
    $role_storage = $this->entityTypeManager->getStorage('group_role');
    // If user has submission admin permission, immediately allow access.
    if ($account->hasPermission('administer webform submissions')) {
      return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::allowed();
    }

    // Load all group relationships for this webform.
    $group_relationship_storage = $this->entityTypeManager->getStorage('group_relationship');
    $group_relationships = $group_relationship_storage->loadByEntity($webform);

    // If the webform does not belong to any group, we have nothing to say.
    if (empty($group_relationships)) {
      return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::neutral();
    }

    // Get all groups to which this webform belongs,
    // including subgroups and parent groups.
    $groups = [];

    foreach ($group_relationships as $group_relationship) {
      $group = $group_relationship->getGroup();
      $groups[] = $group;
    }

    // Check submission access from all groups to which this webform belongs.
    // This will automatically include subgroups
    // because subgroups inherit access permissions from the parent group.
    foreach ($groups as $group) {
      // Multiple group support:
      // If any of the webform submission's groups allow this user access,
      // grant access.
      if ($group->hasPermission('submission_view_any ' . $plugin_id . ' entity', $account)) {
        // If user has submission admin permission within the group,
        // immediately allow access.
        return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::allowed();
      }
      if ($group->hasPermission("$op any $plugin_id submission", $account)
        || $group->hasPermission("$op $plugin_id submission", $account)
      ) {
        // If user can view all submissions, or view submission download.
        return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::allowed();
      }
      elseif (
      // If user can view own submissions, and is the submission owner.
          $group->hasPermission("$op own $plugin_id submission", $account)
          && $webform_submission->isOwner($account)
      ) {
        return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::allowed();
      }
      else {
        $roles = $role_storage->loadByUserAndGroup($account, $group);
        // Add support for outsider roles.
        foreach ($roles as $role) {
          if ($role->getScope() === 'outsider') {
            if ($role->hasPermission("$op any $plugin_id submission")
              || $role->hasPermission("$op own $plugin_id submission")
              || $role->hasPermission("$op $plugin_id submission")) {
              return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::allowed();
            }
            elseif ($role->hasPermission("$op own $plugin_id submission")
              && $webform_submission->isOwner($account)) {

              return $this->webformAccess[$op][$account_id][$webform_id] = AccessResult::allowed();

            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadUserGroupWebformList(AccountInterface $account = NULL) {
    // Used by the Group Webform Select Field Widget
    // to limit te list of available webforms.
    if (!isset($account)) {
      $account = $this->currentUser;
    }
    $allowed_webform_ids = [];
    $account_id = $account->id();
    if (isset($this->userWebformList[$account_id])) {
      return $this->userWebformList[$account_id];
    }

    $group_webforms = $this->getGroupWebforms($account);
    if (!empty($account) && !empty($group_webforms)) {
      // Get all allowed webforms and put their IDs in an array.
      foreach ($group_webforms as $group_webform) {
        foreach ($group_webform as $webform) {
          $allowed_webform_ids[] = $webform->id();
        }
      }
    }
    return $this->userWebformList[$account_id] = $allowed_webform_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupWebforms($account = NULL) {
    if (!$this->groupWebforms) {
      $plugin_id = 'group_webform:webform';
      // Get all webforms, keyed by id.
      $webforms = $this->entityTypeManager->getStorage('webform')
        ->loadMultiple();

      // Get all group webform group relationships.
      $group_relationships = $this->entityTypeManager->getStorage('group_relationship')
        ->loadByPluginId($plugin_id);

      foreach ($group_relationships as $group_relationship) {
        // Make sure group and entity IDs are set in the group relationship
        // entity.
        if (!isset($group_relationship->gid->target_id)
          || !isset($group_relationship->entity_id->target_id)) {
          continue;
        }
        /** @var \Drupal\group\Entity\GroupRelationshipInterface $group_relationship */
        // Group id.
        $gid = $group_relationship->getGroupId();
        // String id of webform.
        $webform_id = $group_relationship->getEntityId();
        if (empty($account)) {
          // If no account is given, return all group-related  webforms.
          $this->groupWebforms[$gid][$webform_id] = $webforms[$webform_id];
        }
        else {
          // If user has admin permissions, return all group-related webforms.
          if ($account->hasPermission('administer webform')) {
            $this->groupWebforms[$gid][$webform_id] = $webforms[$webform_id];
          }
          else {
            // Otherwise check to see if user has access
            // to either create group webforms or relationships
            // to webforms.
            // This also supports access to any Subgroups' webforms,
            // even if the user is not a member of the Subgroup.
            $group = $group_relationship->getGroup();
            if ($group->hasPermission('administer webform', $account)
              || $group->hasPermission('create ' . $plugin_id . ' relationship', $account)
              || $group->hasPermission('create ' . $plugin_id . ' entity', $account)) {
              $this->groupWebforms[$gid][$webform_id] = $webforms[$webform_id];
            }
          }
        }
      }
    }
    return $this->groupWebforms;
  }

}
