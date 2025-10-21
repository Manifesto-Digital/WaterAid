<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Fallback Interface.
 *
 * @package Drupal\wateraid_donation_forms
 */
interface FallbackInterface {

  /**
   * Retrieve the label of the Fallback.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated label.
   */
  public function getLabel(): TranslatableMarkup;

  /**
   * Retrieve the description of this Fallback.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated description.
   */
  public function getDescription(): TranslatableMarkup;

  /**
   * Checks whether the fallback applies.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The interface to check against.
   *
   * @return bool
   *   TRUE if the fallback triggers.
   */
  public function isApplicable(EntityInterface $entity): bool;

}
