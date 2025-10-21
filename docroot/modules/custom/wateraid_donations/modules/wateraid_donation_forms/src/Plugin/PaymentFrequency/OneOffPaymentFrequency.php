<?php

namespace Drupal\wateraid_donation_forms\Plugin\PaymentFrequency;

use Drupal\wateraid_donation_forms\PaymentFrequencyBase;

/**
 * One off payment frequency details.
 *
 * @PaymentFrequency(
 *   id = "one_off",
 *   label = @Translation("One off frequency"),
 *   ui_label = @Translation("One off"),
 *   description = @Translation("One off payment frequency"),
 *   weight = 0
 * )
 *
 * @package Drupal\wateraid_donation_forms\Plugin\PaymentFrequency
 */
class OneOffPaymentFrequency extends PaymentFrequencyBase {}
