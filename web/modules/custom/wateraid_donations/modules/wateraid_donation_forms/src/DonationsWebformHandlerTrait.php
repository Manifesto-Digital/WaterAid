<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\wateraid_donation_forms\Plugin\WebformHandler\DonationsWebformHandler;
use Drupal\webform\WebformInterface;

/**
 * Donations Webform Handler Trait.
 *
 * @package Drupal\wateraid_donation_forms
 */
trait DonationsWebformHandlerTrait {

  /**
   * Get the webform donations handler.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   Webform.
   *
   * @return \Drupal\wateraid_donation_forms\Plugin\WebformHandler\DonationsWebformHandler|bool
   *   Handler.
   *
   * @throws \Exception
   */
  public static function getWebformDonationsHandler(WebformInterface $webform): DonationsWebformHandler|bool {
    $handlers = $webform->getHandlers('wateraid_donations');
    if ($handlers->count() > 0) {
      /** @var \Drupal\wateraid_donation_forms\Plugin\WebformHandler\DonationsWebformHandler $handler */
      return $handlers->getIterator()->current();
    }
    return FALSE;
  }

}
