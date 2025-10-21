<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\webform\WebformSubmissionInterface;

/**
 * PaymentType Interface.
 *
 * @package Drupal\wateraid_donation_forms
 */
interface PaymentTypeInterface {

  /**
   * Get the data column names that describe an instance of this payment type.
   *
   * @return mixed[]
   *   Array of data column names.
   */
  public function getDataColumns(): array;

  /**
   * Get the relevant markup for submission and payment type.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The Webform Submission.
   *
   * @return string
   *   Markup string.
   */
  public function getConfirmationPageMarkup(WebformSubmissionInterface $webform_submission): string;

  /**
   * Get default prefix for payment type.
   *
   * @return string
   *   Prefix.
   */
  public function getPrefix(): string;

}
