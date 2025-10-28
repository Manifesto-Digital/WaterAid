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
    $fields = array_merge([
      'SystemIdentifier',
      'Title',
      'CaptionShort',
      'CaptionLong',
      'MIMEtype',
      'path_TR1',
      'MediaType',
      'Representative_DO'], $fields);

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

    return $this->call($url, 'POST', $bearer);
  }

  /**
   * Get a public URl for a file.
   *
   * @param string $identifier
   *   The unique identifier of the file.
   * @param string|NULL $format
   *   The format of the image.
   * @param int|string|NULL $width
   *   The width of the image.
   * @param int|string|NULL $height
   *   The height of the image.
   * @param bool $download
   *   FALSE to create a view image, TRUE to create a download link.
   * @param string|NULL $bearer
   *   The bearer token to use: will default to a bearer in settings.
   *
   * @return array
   *   The result of the API call. Empty on error.
   */
  public function getPublicLink(string $identifier, string|null $format = NULL, int|string|null $width = NULL, int|string|null $height = NULL, bool $download = FALSE, string $bearer = NULL): array {
    $query = [
      'Identifier' => $identifier,
      'CreateDownloadLink' => ($download) ? 'true' : 'false',
      'ImageResizingMethod' => 'CentreCrop',
      'StickToCurrentVersion' => 'false',
    ];

    if (!empty($format)) {
      $query['Format'] = $format;
    }
    elseif ($width && $height) {
      $query['MaxWidth'] = $width;
      $query['MaxHeight'] = $height;
    }
    else {

      // Default to the original image if no style or width provided.
      $query['Format'] = 'TRX';
    }

    $url = $this->base . '/webapi/objectmanagement/share/getlink_4HZ_v1?' . http_build_query($query);

    return $this->call($url, 'GET', $bearer);
  }

  /**
   * Call the API.
   *
   * @param string $url
   *   The URL to call.
   * @param string $method
   *   A valid http method.
   * @param string|null $bearer
   *   The bearer. Defaults to the bearer in settings if empty.
   *
   * @return array
   *   The result of the API call. Empty on error.
   */
  private function call(string $url, string $method, ?string $bearer = NULL): array {
    $return = [];

    $bearer = ($bearer) ?? Settings::get('orange_dam_bearer');

    try {
      $response = $this->httpClient->request($method, $url, [
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
    catch (\Exception $e) {
      // Do nothing.
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
