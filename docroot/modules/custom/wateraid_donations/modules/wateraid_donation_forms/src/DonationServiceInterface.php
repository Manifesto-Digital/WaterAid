<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Donation Service Interface.
 *
 * @package Drupal\wateraid_donation_forms
 */
interface DonationServiceInterface {

  /**
   * Get the label for the payment provider plugin.
   *
   * @param string $plugin_id
   *   The name of the plugin.
   *
   * @return mixed
   *   Plugin label or FALSE.
   */
  public function getPaymentProviderLabel(string $plugin_id): mixed;

  /**
   * Get all the payment frequency plugins.
   *
   * @param string|null $id
   *   Plugin id.
   *
   * @return \Drupal\wateraid_donation_forms\PaymentFrequencyInterface[]
   *   Initialised PaymentFrequency plugins.
   */
  public function getPaymentFrequencies(?string $id = NULL): array;

  /**
   * Get all the payment type plugins.
   *
   * @param string|null $id
   *   Plugin id.
   *
   * @return \Drupal\wateraid_donation_forms\PaymentTypeInterface[]
   *   Initialised PaymentType plugins.
   */
  public function getPaymentTypes(?string $id = NULL): array;

  /**
   * Get all the payment provider plugins.
   *
   * @param string|null $type
   *   Either all, single, or recurring.
   *
   * @return \Drupal\wateraid_donation_forms\PaymentProviderInterface[]
   *   Initialised PaymentProvider plugins.
   */
  public function getPaymentProvidersByType(?string $type = NULL): array;

  /**
   * Get a payment frequency.
   *
   * @param string $plugin_id
   *   Plugin id.
   *
   * @return bool|object
   *   FALSE or the payment frequency plugin instance.
   */
  public function getPaymentFrequency(string $plugin_id): bool|object;

  /**
   * Get a payment provider.
   *
   * @param string $plugin_id
   *   Plugin id.
   *
   * @return bool|object
   *   FALSE or the payment provider plugin instance.
   */
  public function getPaymentProvider(string $plugin_id): bool|object;

  /**
   * Get a payment type.
   *
   * @param string $plugin_id
   *   Plugin id.
   *
   * @return bool|object
   *   FALSE or the payment type plugin instance.
   */
  public function getPaymentType(string $plugin_id): bool|object;

  /**
   * Get In Memory element data if present.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The Webform Submission.
   *
   * @return bool|mixed[]
   *   FALSE or an array of element data.
   *
   * @see \Drupal\wateraid_donation_forms\Plugin\WebformElement\DonationsWebformInMemory
   */
  public function getInMemoryData(WebformSubmissionInterface $webform_submission): bool|array;

  /**
   * Get DateTime for last fixed period subscription.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The donaation webform submission.
   *
   * @return \DateTime|null
   *   The DateTime for last payment, NULL if none.
   */
  public function getFixedPeriodDateEnd(WebformSubmissionInterface $webform_submission): \DateTime|NULL;

}
