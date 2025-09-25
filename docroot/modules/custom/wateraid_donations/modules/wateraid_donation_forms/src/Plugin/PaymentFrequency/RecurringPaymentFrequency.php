<?php

namespace Drupal\wateraid_donation_forms\Plugin\PaymentFrequency;

use Drupal\wateraid_donation_forms\PaymentFrequencyBase;

/**
 * A Recurring Payment frequency plugin.
 *
 * @PaymentFrequency(
 *   id = "recurring",
 *   label = @Translation("Recurring frequency"),
 *   ui_label = @Translation("Monthly"),
 *   description = @Translation("Recurring payment frequency"),
 *   weight = 1
 * )
 *
 * @package Drupal\wateraid_donation_forms\Plugin\PaymentFrequency
 */
class RecurringPaymentFrequency extends PaymentFrequencyBase {}
