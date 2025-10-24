<?php

namespace Drupal\wateraid_donation_forms\Plugin\PaymentFrequency;

use Drupal\wateraid_donation_forms\PaymentFrequencyBase;

/**
 * A Recurring Payment frequency plugin.
 *
 * @PaymentFrequency(
 *   id = "fixed_period",
 *   label = @Translation("Fixed period"),
 *   ui_label = @Translation("Fixed period"),
 *   description = @Translation("Fixed period"),
 *   has_duration = TRUE,
 *   weight = 1
 * )
 *
 * @package Drupal\wateraid_donation_forms\Plugin\PaymentFrequency
 */
class FixedPeriodPaymentFrequency extends PaymentFrequencyBase {}
