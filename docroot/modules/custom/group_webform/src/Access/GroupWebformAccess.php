<?php

namespace Drupal\group_webform\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Checks access for displaying webform pages.
 */
class GroupWebformAccess implements GroupWebformAccessInterface {

  /**
   * {@inheritdoc}
   */
  public function webformAdministerAccess(AccountInterface $account, WebformInterface $webform = NULL) {
    // Empty checks prevent error when other modules like Easy Breadcrumb
    // check a custom access route like entity.webform.user.submission
    // to get the title.
    if (empty($webform)) {
      return AccessResult::neutral();
    }
    return \Drupal::service('groupwebform.webform')->webformAccess('administer webform', $webform, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function webformViewAccess(AccountInterface $account, WebformInterface $webform) {
    if (empty($webform)) {
      return AccessResult::neutral();
    }
    return \Drupal::service('groupwebform.webform')->webformAccess('view', $webform, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function webformEditAccess(AccountInterface $account, WebformInterface $webform = NULL) {
    if (empty($webform)) {
      return AccessResult::neutral();
    }
    return \Drupal::service('groupwebform.webform')->webformAccess('update', $webform, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function webformDeleteAccess(AccountInterface $account, WebformInterface $webform) {
    if (empty($webform)) {
      return AccessResult::neutral();
    }
    return \Drupal::service('groupwebform.webform')->webformAccess('delete', $webform, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function webformResultsAccess(AccountInterface $account, WebformInterface $webform = NULL) {
    if (empty($webform)) {
      return AccessResult::neutral();
    }
    return \Drupal::service('groupwebform.webform')->webformAccess('submission_view_any', $webform, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function webformSubmissionViewAccess(AccountInterface $account, WebformSubmissionInterface $webform_submission = NULL) {
    if (empty($webform_submission)) {
      return AccessResult::neutral();
    }
    return \Drupal::service('groupwebform.webform')->webformSubmissionAccessItems('view', $webform_submission, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function webformSubmissionEditAccess(AccountInterface $account, WebformSubmissionInterface $webform_submission) {
    if (empty($webform_submission)) {
      return AccessResult::neutral();
    }
    return \Drupal::service('groupwebform.webform')->webformSubmissionAccessItems('update', $webform_submission, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function webformSubmissionDeleteAccess(AccountInterface $account, WebformSubmissionInterface $webform_submission) {
    if (empty($webform_submission)) {
      return AccessResult::neutral();
    }
    return \Drupal::service('groupwebform.webform')->webformSubmissionAccessItems('delete', $webform_submission, $account);
  }

}
