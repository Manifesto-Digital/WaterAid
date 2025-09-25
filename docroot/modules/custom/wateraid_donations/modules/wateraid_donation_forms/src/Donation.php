<?php

namespace Drupal\wateraid_donation_forms;

/**
 * Class Donation.
 *
 * A plain donation class.
 *
 * @package Drupal\wateraid_donation_forms
 */
class Donation {

  /**
   * The donation currency sign.
   */
  public string $currencySign;

  /**
   * The donation amount.
   */
  public string $amount;

  /**
   * Gets the donation currency sign.
   *
   * @return string
   *   The currency sign.
   */
  public function getCurrencySign(): string {
    return $this->currencySign;
  }

  /**
   * Gets the donation amount.
   *
   * @return string
   *   The amount.
   */
  public function getAmount(): string {
    return $this->amount;
  }

  /**
   * Sets the donation currency sign.
   *
   * @param string $currency_sign
   *   The currency sign.
   *
   * @return $this
   */
  public function setCurrencySign(string $currency_sign): static {
    $this->currencySign = $currency_sign;
    return $this;
  }

  /**
   * Sets the donation amount.
   *
   * @param string $amount
   *   The amount.
   *
   * @return $this
   */
  public function setAmount(string $amount): static {
    $this->amount = $amount;
    return $this;
  }

}
