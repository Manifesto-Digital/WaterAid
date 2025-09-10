<?php

namespace Drupal\just_giving;

/**
 * Interface for the Just Giving Countries.
 */
interface JustGivingCountriesInterface {

  /**
   * Get countries form list.
   *
   * @return string[]|null
   *   Returns country list values.
   */
  public function getCountriesFormList(): ?array;

}
