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
  public function search(array $query) {
    $url = $this->base . 'API/search/v4.0/search';

    $query['fields'] = 'SystemIdentifier, Title';
    $query['format'] = 'json';
    $body = Json::encode($query);

    // This is the example call provided by Orange DAM. Currently it fails as
    // unauthorized so we will add our body params once we know it works.
    $response = $this->httpClient->request('POST', $url, [
      'body' => '{"countperpage":100,"pagenumber":1,"verbose":false,"generateformatifnotexists":false,"getpermanentassetspaths":false,"disableURIencoding":false,"includebinned":false,"includeassetswithvirtualpaths":false,"format":"json"}',
      'headers' => [
        'accept' => 'application/json',
        'authorization' => 'Bearer ' . Settings::get('orange_dam_bearer'),
        'content-type' => 'application/json',
      ],
    ]);
  }

}
