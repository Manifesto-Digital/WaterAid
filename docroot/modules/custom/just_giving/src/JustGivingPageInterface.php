<?php

namespace Drupal\just_giving;

/**
 * Interface for the Just Giving Page.
 */
interface JustGivingPageInterface {

  /**
   * Set user info.
   *
   * @param mixed[] $userInfo
   *   User info array.
   */
  public function setUserInfo(array $userInfo): void;

  /**
   * Set page info.
   *
   * @param mixed $pageInfo
   *   Page info.
   */
  public function setPageInfo(mixed $pageInfo): void;

  /**
   * Register fund raising page.
   *
   * @return string
   *   The HTML string for the page.
   */
  public function registerFundraisingPage(): string;

}
