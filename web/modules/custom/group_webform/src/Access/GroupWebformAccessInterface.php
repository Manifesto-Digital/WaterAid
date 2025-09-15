<?php

namespace Drupal\group_webform\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an interface defining a WebformAdmin manager.
 */
interface GroupWebformAccessInterface {

  /**
   * A custom access check for webform admin permissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\webform\WebformInterface $webform
   *   Run access checks for this webform object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function webformAdministerAccess(AccountInterface $account, WebformInterface $webform);

  /**
   * A custom access check for webform view actions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\webform\WebformInterface $webform
   *   Run access checks for this webform object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function webformViewAccess(AccountInterface $account, WebformInterface $webform);

  /**
   * A custom access check for webform edit actions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\webform\WebformInterface $webform
   *   Run access checks for this webform object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function webformEditAccess(AccountInterface $account, WebformInterface $webform);

  /**
   * A custom access check for webform delete actions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\webform\WebformInterface $webform
   *   Run access checks for this webform object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function webformDeleteAccess(AccountInterface $account, WebformInterface $webform);

  /**
   * A custom access check for webform submission results.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\webform\WebformInterface $webform
   *   Run access checks for this webform item object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function webformResultsAccess(AccountInterface $account, WebformInterface $webform);

  /**
   * A custom access check for submission view/view_all/view_own.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Run access checks for this webform link object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function webformSubmissionViewAccess(AccountInterface $account, WebformSubmissionInterface $webform_submission);

  /**
   * A custom access check for webform submissions edit page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Run access checks for this webform link object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function webformSubmissionEditAccess(AccountInterface $account, WebformSubmissionInterface $webform_submission);

  /**
   * A custom access check for webform submissions delete page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Run access checks for this webform link object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function webformSubmissionDeleteAccess(AccountInterface $account, WebformSubmissionInterface $webform_submission);

}
