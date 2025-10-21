<?php

namespace Drupal\just_giving;

/**
 * Just Giving Search.
 */
class JustGivingSearch implements JustGivingSearchInterface {

  /**
   * Drupal\just_giving\JustGivingClient definition.
   */
  protected JustGivingClient $justGivingClient;

  /**
   * JustGivingCountries constructor.
   *
   * @param \Drupal\just_giving\JustGivingClientInterface $just_giving_client
   *   The just giving client interface.
   */
  public function __construct(JustGivingClientInterface $just_giving_client) {
    $this->justGivingClient = $just_giving_client;
  }

  /**
   * Charity search.
   *
   * @param string $search_text
   *   Input text to search charities.
   * @param int $max_items
   *   Max number of items returned.
   *
   * @return mixed
   *   Returns mixed value.
   */
  public function charitySearch(string $search_text, int $max_items = 20): mixed {

    if (!$this->justGivingClient->jgLoad()) {
      return NULL;
    }
    else {
      return $this->justGivingClient->jgLoad()->Search->CharitySearch($search_text, $max_items, 1);
    }
  }

  /**
   * Event search.
   *
   * @param string $search_text
   *   Input text to search events.
   * @param int $max_items
   *   Max number of items returned.
   *
   * @return mixed
   *   NULL or event search results.
   */
  public function eventSearch(string $search_text, int $max_items = 20): mixed {
    if (!$this->justGivingClient->jgLoad()) {
      return NULL;
    }
    return $this->justGivingClient->jgLoad()->Search->EventSearch($search_text, $max_items, 1);
  }

}
