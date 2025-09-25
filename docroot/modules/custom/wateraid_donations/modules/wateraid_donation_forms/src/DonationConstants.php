<?php

namespace Drupal\wateraid_donation_forms;

/**
 * Class DonationConstants.
 *
 * Pre-defined donation related strings.
 *
 * @package Drupal\wateraid_donation_forms
 */
abstract class DonationConstants {

  /**
   * Prefix for data attributes relevant to donations.
   *
   * @note Do not confuse this with the CardPaymentType prefix attr!
   */
  public const DONATION_PREFIX = 'donation__';

  /**
   * Category used for Donation Webforms.
   */
  public const DONATION_CATEGORY = 'Donation';

}
