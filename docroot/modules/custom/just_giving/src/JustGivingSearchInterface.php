<?php

namespace Drupal\just_giving;

/**
 * Search Interface.
 */
interface JustGivingSearchInterface {

  /**
   * Performs a charity search.
   *
   * @param string $search_text
   *   The search text.
   * @param int $max_items
   *   The maximum items to return.
   *
   * @return mixed
   *   The search result or NULL on error.
   */
  public function charitySearch(string $search_text, int $max_items): mixed;

}
