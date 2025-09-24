<?php

namespace Drupal\group_webform\Plugin\Group\RelationHandler;

use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderTrait;

/**
 * Provides group permissions for the group_webform relation plugin.
 */
class GroupWebformPermissionProvider implements PermissionProviderInterface {

  use PermissionProviderTrait;

  /**
   * Constructs a new GroupWebformPermissionProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $parent
   *   The default permission provider.
   */
  public function __construct(PermissionProviderInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($operation, $target, $scope = 'any') {
    if (!isset($this->parent)) {
      throw new \LogicException('Using PermissionProviderTrait without assigning a parent or overwriting the methods.');
    }

    if ($operation === 'view unpublished' && $target === 'entity' && $scope === 'any') {
      return "$operation $this->pluginId $target";
    }

    // Take care of extra Webform entity operations.
    if ($target === 'entity') {
      switch ($operation) {
        case 'submissions':
          return "access $this->pluginId submissions";
      }
    }

    return $this->parent->getPermission($operation, $target, $scope);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    // Based on:
    // https://www.drupal.org/project/group/issues/3345316#comment-14948010
    // https://www.hashbangcode.com/article/drupal-10-adding-custom-permissions-groups
    // NOTE: For expediency's sake, the following permissions are condensed
    // under 'administer webform':
    // * 'administer webform submissions'
    // * 'access any webform configuration'
    // * 'access own webform configuration'
    // * 'administer webform element access'
    // * 'edit webform source'
    // * 'edit webform twig'
    // * 'edit webform assets'
    // * 'edit webform variants'
    // * 'test webform variants'
    // * Purge any permissions:
    // *   webform.submission_purge_any
    // *   collection purge
    // * webform help
    // * webform test
    // * webform duplication
    // * notes
    //
    // 'access webform submission user' has been condensed
    // under 'create submission' permission.
    // see src/Plugin/Group/RelationHandler/GroupWebformPermissionProvider.php
    // for more details.
    //
    // The 'view user submission page' permission
    // is not being granted or checked at the group level;
    // this module allows the webform sitewide permission
    // to provide that access check, because we wouldn't want
    // to show/hide individual submissions from that page
    // based on user group access.  User access can still be
    // restricted by inherited group role, if necessary.
    //
    $permissions = $this->parent->buildPermissions();
    $prefix = 'Entity:';

    // Instead of checking whether this specific permission provider allows for
    // a permission to exist, we check the entire decorator chain. This avoids a
    // lot of copy-pasted code to turn off or rename a permission in a decorator
    // further down the chain.
    $provider_chain = $this->groupRelationTypeManager()->getPermissionProvider($this->pluginId);

    if ($provider_chain->getPermission('view', 'entity')) {
      // WEBFORM-SPECIFIC PERMISSIONS
      // Adding group-specific, webform-related permissions
      // not provided as default permissions by Group Relationship Builder.
      // 'Administer Webform' Allows users to administer webforms
      // at Group level.
      $administerWebform = 'administer webform';
      $permissions[$administerWebform] =
        $this->buildPermission(
          "$prefix Administer %entity_type",
          "Warning: Give to trusted roles only; this permission
          has security implications. Allows administration,
          at Group level, of global YAML configuration and options.
          Also includes access to edit all %entity_type configuration
          including Twig templates, source code,
          JS/CSS assets, and variants");

      // Webform.submission_view_any: Allows users to view
      // Submission results page, export results, customize user table.
      $viewAnySubmission = 'submission_view_any ' . $this->pluginId . ' entity';
      $permissions[$viewAnySubmission] =
        $this->buildPermission(
          "$prefix Administer %entity_type submissions",
          "Required to view a %entity_type's Results page.
          Warning: Give to trusted roles only; this permission has
          security implications. Note: To allow users to administer
          an individual %entity_type's submissions, please go to the %entity_type's
          'Access' tab. Allows accessing, updating, and deleting
          all %entity_type submissions at Group level.");

      // SUBMISSION-SPECIFIC PERMISSIONS.
      // Webform_submission.view_any.
      $viewAny = 'view any ' . $this->pluginId . ' submission';
      $permissions[$viewAny] =
        $this->buildPermission(
          "$prefix View any %entity_type submission",
          "Allows viewing any individual group %entity_type submission.");

      // Webform_submission.view_own:
      $viewOwn = 'view own ' . $this->pluginId . ' submission';
      $permissions[$viewOwn] =
        $this->buildPermission(
          "$prefix View own %entity_type submission",
          "Warning: This permission affects access to all %entity_type.
          Note: To allow users to view own submissions for a
          individual %entity_type, please go to the %entity_type's 'Access' tab.
          Allows viewing own submissions for all %entity_type.");
    }

    // Delete permissions
    // Webform_submission.delete_own:
    if ($provider_chain->getPermission('delete', 'entity', 'any')) {
      $deleteAny = 'delete any ' . $this->pluginId . ' submission';
      $permissions[$deleteAny] =
        $this->buildPermission("
          $prefix Delete any %entity_type submission",
          "Allows deleting all submissions.");
    }

    if ($provider_chain->getPermission('delete', 'entity', 'own')) {
      $deleteOwn = 'delete own ' . $this->pluginId . ' submission';
      $permissions[$deleteOwn] =
        $this->buildPermission(
          "$prefix Delete own %entity_type submission",
          "Warning: This permission affects access to all %entity_types.
          Note: To allow users to delete own submissions for an individual
          %entity_type, please go to the %entity_type's 'Access' tab.
          Allows deleting own submissions for all %entity_types.");
    }

    // Edit permissions.
    if ($provider_chain->getPermission('update', 'entity', 'any')) {
      $editAny = 'update any ' . $this->pluginId . ' submission';
      $permissions[$editAny] =
        $this->buildPermission("
          $prefix Edit any %entity_type submission",
          "Allows updating all submissions.");
    }

    if ($provider_chain->getPermission('update', 'entity', 'own')) {
      $editOwn = 'update own ' . $this->pluginId . ' submission';
      $permissions[$editOwn] =
        $this->buildPermission("
          $prefix Edit own %entity_type submission",
          "Allows updating own submissions for all %entity_types.
          Warning: This permission affects access to all %entity_types.
          Note: To allow users to update own submissions for an
          individual %entity_type, please go to the %entity_type's 'Access' tab.
          Allows viewing own submissions for all %entity_types.");
    }
    return $permissions;
  }

}
