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
   * Performs a search against the Orange API.
   *
   * @param array $query
   *   The query parameters: <parameter> => <value>
   * @param array $fields
   *   The returned fields: [<field Identifier>, <field Identifier>]
   * @param string|NULL $bearer
   *   The bearer token to use: will default to a bearer in settings.
   *
   * @return array
   *   The return from the search call. Empty on error.
   */
  public function search(array $query = [], array $fields = [], string $bearer = NULL): array {
    $return = [];

    $bearer = ($bearer) ?? Settings::get('orange_dam_bearer');

    $fields = array_merge([
      'SystemIdentifier',
      'Title',
      'CaptionShort',
      'CaptionLong',
      'MIMEtype',
      'path_TR1',
      'MediaType'], $fields);

    $query = array_merge([
      'format' => 'JSON',
      'fields' => implode(',', $fields),
      'countperpage' => 100,
      'pagenumber' => 1,
      'verbose' => TRUE,
      'generateformatifnotexists' => FALSE,
      'getpermanentassetspaths' => TRUE,
      'disableURIencoding' => FALSE,
      'includebinned' => FALSE,
      'includeassetswithvirtualpaths' => TRUE,
    ], $query);

    $url = $this->base . 'API/search/v4.0/search?' . http_build_query($query);

    $response = $this->httpClient->request('POST', $url, [
      'headers' => [
        'accept' => 'application/json',
        'Authorization' => 'Bearer ' . $bearer,
        'content-type' => 'application/json',
      ],
    ]);

    if ($body = $response->getBody()->getContents()) {
      $return = Json::decode($body);
    }

    return $return;
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
