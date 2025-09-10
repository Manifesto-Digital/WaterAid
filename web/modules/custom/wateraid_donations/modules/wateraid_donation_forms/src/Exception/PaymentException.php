<?php

namespace Drupal\wateraid_donation_forms\Exception;

/**
 * Payment Exception.
 *
 * @package Drupal\wateraid_donation_forms
 */
class PaymentException extends \Exception {

  public const SYSTEM_ERROR = 1;
  public const PAYMENT_FAILURE = 2;
  public const PAYMENT_FAILURE_STATUS = 'failure';

}
