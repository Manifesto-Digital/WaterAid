<?php

namespace Drupal\just_giving;

/**
 * Just Giving Account interface.
 */
interface JustGivingAccountInterface {

  /**
   * Sets the address details.
   *
   * @param mixed[] $jgAddressDetails
   *   The address details to set.
   */
  public function setJgAddressDetails(array $jgAddressDetails): void;

  /**
   * Sets the account details.
   *
   * @param mixed[] $jgAccountDetails
   *   The account details to set.
   */
  public function setJgAccountDetails(array $jgAccountDetails): void;

  /**
   * Checked whether the account already exists.
   *
   * @param string $user_email
   *   The account email address.
   */
  public function checkAccountExists(string $user_email): mixed;

  /**
   * Validate the account.
   *
   * @param string $email
   *   The email address.
   * @param string $password
   *   The account password.
   */
  public function validateAccount(string $email, string $password): mixed;

  /**
   * Creates the account.
   *
   * @return mixed
   *   The return from the account creation service.
   */
  public function createAccount(): mixed;

}
