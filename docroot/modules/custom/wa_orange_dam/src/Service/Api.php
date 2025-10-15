<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Site\Settings;
use Psr\Http\Client\ClientInterface;

/**
 * Orange DAM API connections.
 */
final class Api {

  /**
   * Constructs an AzureApi object.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
  ) {}

  /**
   * Base URL of the Orange DAM.
   *
   * @var string
   */
  private string $base = 'https://dam.wi0.orangelogic.com/';

  /**
   * Performs a search on the Orange DAM.
   *
   * @param array $query
   *   The query parameters. Will be turned into JSOn for the call.
   */
  public function search(array $query = [], string $bearer = NULL) {
    $url = $this->base . 'API/search/v4.0/search';

    $bearer = ($bearer) ?? Settings::get('orange_dam_bearer');

    $query['fields'] = 'SystemIdentifier, Title';
    $query['format'] = 'json';

    // This is the example call provided by Orange DAM. Currently it fails as
    // unauthorized so we will add our body params once we know it works.
    $response = $this->httpClient->request('POST', $url, [
      'form_params' => [
        "countperpage" > 100,
        "pagenumber" => 1,
        "verbose" => false,
        "generateformatifnotexists" => false,
        "getpermanentassetspaths" => false,
        "disableURIencoding" => false,
        "includebinned" => false,
        "includeassetswithvirtualpaths" => false,
        "format" => "json",
      ],
      'headers' => [
        'accept' => 'application/json',
        'Authorization' => 'Bearer ' . $bearer,
        'content-type' => 'application/json',
      ],
    ]);

    if ($body = $response->getBody()->getContents()) {
      $return = Json::decode($body);
    }
  }

  /**
   * Authorize against the API and obtain a bearer token.
   *
   * @return array|null
   *   A response array or NULL on error.
   */
  public function authorize(): ?array {
    $return = NULL;

    $response = $this->httpClient->request('POST', $this->base . 'webapi/security/clientcredentialsauthentication/authenticate_46H_v1', [
      'form_params' => [
        'grant_type' => 'client_credentials',
        'client_id' => Settings::get('orange_dam_id'),
        'client_secret' => Settings::get('orange_dam_secret'),
      ],
      'headers' => [
        'accept' => 'application/json',
        'content-type' => 'application/x-www-form-urlencoded',
      ],
    ]);

    if ($body = $response->getBody()->getContents()) {
      $return = Json::decode($body);
    }

    return $return;
  }

}
