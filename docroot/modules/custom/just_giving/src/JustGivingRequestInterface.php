<?php

namespace Drupal\just_giving;

use Drupal\Core\Form\FormStateInterface;

/**
 * Just Giving request interface.
 */
interface JustGivingRequestInterface {

  /**
   * Create fundraising page.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string
   *   Returns the HTML page string.
   */
  public function createFundraisingPage(FormStateInterface $form_state): string;

}
