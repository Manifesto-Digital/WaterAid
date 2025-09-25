<?php

namespace Drupal\loqate_email;

/**
 * Interface for the validator.
 */
interface ValidatorInterface {

  /**
   * Method for validating email addresses against loqate.
   *
   * @param string $email
   *   The email address to validate.
   * @param bool $refuse_disposable_temporary_emails
   *   Treat an "IsDisposableOrTemporary" response from the Loqate API as an
   *   invalid email address. See https://www.loqate.com/resources/support/apis/EmailValidation/Interactive/Validate/2/.
   *
   * @return mixed[]
   *   Result of the validation including:
   *
   *   skipped (bool):
   *    - TRUE if the Loqate API call was skipped due to API key configuration.
   *
   *   valid (bool):
   *    - TRUE if the email is valid, FALSE if invalid.
   *    - Note that this takes $refuse_disposable_temporary_emails into account.
   *
   *   invalid_email_error_message (string):
   *    - Globally configured error message for invalid Loqate email addresses.
   */
  public function validateEmailAddress(string $email, bool $refuse_disposable_temporary_emails = TRUE): array;

  /**
   * Method to retrieve the default error message.
   *
   * @return string|null
   *   The globally configured error message.
   */
  public function getErrorMessage(): ?string;

  /**
   * Gets the configured API timeout in milliseconds.
   *
   * If the configured valid is null or out of range (1 - 15000)
   * the default timeout of 15000 will be returned.
   *
   * @return int
   *   Timeout in ms.
   */
  public function getApiTimeoutMs(): int;

  /**
   * Generates a hash with the email address.
   *
   * @param string $email
   *   The email to hash.
   *
   * @return string
   *   A hash.
   */
  public function getHash(string $email): string;

  /**
   * Gets cache tags from dependent config.
   *
   * @return string[]|null
   *   An array of cache tags.
   */
  public function getCacheTags(): ?array;

}
