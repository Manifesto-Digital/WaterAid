<?php

namespace Drupal\group_webform;

use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an interface defining a WebformAdmin manager.
 */
interface GroupWebformServiceInterface {

  /**
   * Gets Config.
   */
  public function getConfig();

  /**
   * Sets Config.
   *
   * @var string $key
   *   The config parameter
   * @var string $value
   *   The value to update.
   */
  public function setConfig(string $key, string $value);

  /**
   * A custom access check for a webform operation.
   *
   * @param string $op
   *   The operation to perform on the webform.
   * @param \Drupal\webform\WebformInterface $webform
   *   Run access checks for this webform object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function webformAccess($op, WebformInterface $webform = NULL, AccountInterface $account = NULL);

  /**
   * A custom access check for a webform submissions.
   *
   * @param string $op
   *   The operation to perform on the webform.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Run access checks for this webform object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function webformSubmissionAccessItems($op, WebformSubmissionInterface $webform_submission, AccountInterface $account = NULL);

  /**
   * Loads list of all group-related webforms to which user has access.
   *
   * Resulting list is of webform IDs only, not keyed by group.
   * Used by GroupWebformEntityReferenceSelect
   * and Group Webform EntityReferenceAutocomplete to reduce
   * available options to only those group webforms that a user may access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return array
   *   An array of webform IDs keyed by webform name.
   */
  public function loadUserGroupWebformList(AccountInterface $account = NULL);

  /**
   * Get all group webform objects.
   *
   * We create a static cache of group webforms since loading them individually
   * has a big impact on performance.
   *
   * Loads all group-related webforms if $account is null.
   * With $account, loads all group-related webforms to which a user
   * has create access, keyed by Group ID.
   *
   * @return \Drupal\webform\WebformInterface[]
   *   A nested array containing all group webform objects keyed by group ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupWebforms($account = NULL);

}
