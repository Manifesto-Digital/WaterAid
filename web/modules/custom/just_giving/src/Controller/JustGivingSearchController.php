<?php

namespace Drupal\just_giving\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\just_giving\JustGivingClient;
use Drupal\just_giving\JustGivingSearch;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for autocomplete search elements.
 */
class JustGivingSearchController extends ControllerBase {

  /**
   * Drupal\just_giving\JustGivingClient definition.
   */
  protected JustGivingClient $justGivingClient;

  /**
   * Drupal\just_giving\JustGivingSearch definition.
   */
  protected JustGivingSearch $justGivingSearch;

  /**
   * Constructs a new SearchController object.
   */
  public function __construct() {
    $this->justGivingClient = new JustGivingClient();
    $this->justGivingSearch = new JustGivingSearch($this->justGivingClient);
  }

  /**
   * Handler for autocomplete request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   * @param string $search_type
   *   Search type.
   * @param string $field_name
   *   Field name passed.
   * @param int $count
   *   Number of items returned.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns JSON response.
   */
  public function handleAutocomplete(Request $request, string $search_type, string $field_name, int $count): JsonResponse {
    $results = [];

    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      switch ($search_type) {
        case 'charity':
          $results = $this->charitySearch($input, $count);
          break;

        case 'event':
          $results = $this->eventSearch($input, $count);
          break;
      }
    }

    return new JsonResponse($results);
  }

  /**
   * Charity search.
   *
   * @param string $input
   *   Input parameter.
   * @param int $count
   *   Count parameter.
   *
   * @return mixed[]
   *   Returns the results array.
   */
  private function charitySearch(string $input, int $count): array {
    $results = [];
    $jgSearchResults = $this->justGivingSearch->charitySearch($input, $count);
    if (!empty($jgSearchResults->charitySearchResults)) {
      foreach ($jgSearchResults->charitySearchResults as $item) {
        $results[] = [
          'value' => $item->charityId,
          'label' => $item->charityDisplayName . ' (' . $item->charityId . ')',
        ];
      }
    }
    return $results;
  }

  /**
   * Event search.
   *
   * @param string $input
   *   The search input.
   * @param int $count
   *   The count parameter.
   *
   * @return mixed[]
   *   Returns an array of results.
   */
  private function eventSearch(string $input, int $count): array {
    $results = [];
    $jgSearchResults = $this->justGivingSearch->eventSearch($input, $count);
    if (!empty($jgSearchResults->events)) {
      foreach ($jgSearchResults->events as $item) {
        $results[] = [
          'value' => $item->id,
          'label' => $item->name . ' (' . $item->id . ')',
        ];
      }
    }
    return $results;
  }

}
