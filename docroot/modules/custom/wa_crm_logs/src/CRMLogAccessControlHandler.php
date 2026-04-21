<?php

declare(strict_types=1);

namespace Drupal\wa_crm_logs;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the crm log entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class CRMLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view crm_log'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit crm_log'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete crm_log'),
      'delete revision' => AccessResult::allowedIfHasPermission($account, 'delete crm_log revision'),
      'view all revisions', 'view revision' => AccessResult::allowedIfHasPermissions($account, ['view crm_log revision', 'view crm_log']),
      'revert' => AccessResult::allowedIfHasPermissions($account, ['revert crm_log revision', 'edit crm_log']),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create crm_log', 'administer crm_log'], 'OR');
  }

}
