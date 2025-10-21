<?php

namespace Drupal\just_giving;

/**
 * Just Giving client interface.
 */
interface JustGivingClientInterface {

  /**
   * Main call for just giving client.
   *
   * @return mixed
   *   returns mixed value.
   */
  public function jgLoad(): mixed;

  /**
   * Set username method.
   *
   * @param string $username
   *   The username parameter.
   */
  public function setUsername(string $username): void;

  /**
   * Set password method.
   *
   * @param string $password
   *   The password parameter.
   */
  public function setPassword(string $password): void;

}
