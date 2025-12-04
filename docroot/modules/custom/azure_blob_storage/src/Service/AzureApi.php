<?php

declare(strict_types=1);

namespace Drupal\azure_blob_storage\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Http\Client\ClientInterface;

/**
 * Service for connecting to Azure Blob Storage.
 */
final class AzureApi {

  use StringTranslationTrait;

  /**
   * Constructs an AzureApi object.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * The API version to use.
   *
   * @var string
   */
  private string $apiVersion = '2019-12-12';

  /**
   * The Blob storage account name to use.
   *
   * @var string
   */
  private string $accountName = 'watestcrmlocation';

  /**
   * The default container to use.
   *
   * @var string
   */
  private string $container = 'wateraid-webforms';

  /**
   * Allows the user to override the default container.
   *
   * @param string $container
   *   An alternative container to use.
   */
  public function setAccountName(string $accountName): void {
    $this->accountName = $accountName;
  }


  /**
   * Allows the user to override the default container.
   *
   * @param string $container
   *   An alternative container to use.
   */
  public function setContainer(string $container): void {
    $this->container = $container;
  }

  /**
   * Gets the private key from settings.
   *
   * @return string|FALSE
   *   The shared key, or FALSE on error.
   */
  private function sharedKey(): string|bool {
    return Settings::get('azure_blob_storage_key');
  }

  /**
   * Send a blob to the blob storage.
   *
   * @param string $blob_name
   *   The name of the blob.
   * @param array $blob_data
   *   The blob content.
   * @param bool $is_queue
   *   Whether this is being triggered by the queued process. Default: FALSE.
   *
   * @return bool
   *   Whether the PUT succeeded or failed.
   */
  public function blobPut(string $blob_name, array $blob_data, $is_queue = FALSE): bool {
    $result = FALSE;

    $blob = Json::encode($blob_data);
    $content_type = 'application/json';
    $date = $this->dateGet();
    $http_method = 'PUT';
    $xmsHeaders = [
      'x-ms-version' => $this->apiVersion,
      'x-ms-blob-type' => 'BlockBlob',
    ];

    $contentMD5 = base64_encode(md5($blob, TRUE));

    $signature = $this->signatureCreate(
      $http_method,
      '',
      '',
      strlen($blob),
      $contentMD5,
      $content_type,
      $date,
      $xmsHeaders,
      "/$this->accountName/$this->container/$blob_name"
    );

    if (getenv('BLOB_STORAGE_DEBUG') == 'true') {
      $this->logger->debug("accountName: " . $this->accountName);
      $this->logger->debug("container: " . $this->container);
      $this->logger->debug("blob_name: " . $blob_name);
    }

    if ($key = $this->sharedKey()) {
      $encodedSignature = base64_encode(
        hash_hmac(
          "sha256",
          mb_convert_encoding($signature, 'UTF-8', 'ISO-8859-1'),
          base64_decode($key),
          TRUE
        )
      );
    }
    else {
      return FALSE;
    }

    $headers = array_merge(
      [
        'Authorization' => "SharedKey $this->accountName:$encodedSignature",
        'Content-MD5' => $contentMD5,
        'Content-Type' => $content_type,
        'Date' => $date,
      ],
      $xmsHeaders
    );

    try {

      /** @var \GuzzleHttp\Psr7\Response $response */
      $response = $this->httpClient->request($http_method, "https://$this->accountName.blob.core.windows.net/$this->container/$blob_name", [
        'headers' => $headers,
        'body' => $blob,
      ]);

      // Double-check the status before we say this has succeeded.
      if ($status = $response->getStatusCode()) {
        if ($status >= 200 && $status < 300) {
          $result = TRUE;
        }
      }
    }
    catch (\Exception $e) {

      // The queue using this service will manage retries, so we only need to
      // log the issue here if this isn't the queue.
      if (!$is_queue) {
        $this->logger->error($this->t('There was a problem pushing data to the azure blob storage: :e', [
          ':e' => $e->getMessage(),
        ]));
      }
    }

    return $result;
  }

  /**
   * Generate the canonical headers.
   *
   * @param array $headers
   *   An array of header data.
   *
   * @return string
   *   The header string.
   */
  private function canonicalHeadersCreate(array $headers): string {
    $canonicalHeaderString = '';

    $keys = array_keys($headers);
    sort($keys);

    foreach ($keys as $key) {
      $canonicalHeaderString .= "$key:{$headers[$key]}\n";
    }

    return $canonicalHeaderString;
  }

  /**
   * Get the formatted date.
   *
   * @return string
   *   The RFC7231 formatted date.
   */
  private function dateGet(): string {
    $date_time = new DrupalDateTime();

    return $date_time->format(\DateTimeInterface::RFC7231);
  }

  /**
   * Generate the signature.
   *
   * @param string $verb
   *   The http method being used.
   * @param string $contentEncoding
   *   The character encoding.
   * @param string $contentLanguage
   *   The content language.
   * @param int $contentLength
   *   The content length.
   * @param string $contentMD5
   *   The content MD5.
   * @param string $contentType
   *   The content type.
   * @param string $date
   *   The request date.
   * @param array $headers
   *   The x-ms- headers.
   * @param string $canonicalResource
   *   The canonical resource.
   *
   * @return string
   *   The signature.
   */
  public function signatureCreate(
    string $verb,
    string $contentEncoding,
    string $contentLanguage,
    int $contentLength,
    string $contentMD5,
    string $contentType,
    string $date,
    array $headers,
    string $canonicalResource,
  ): string {

    $canonicalHeaderString = $this->canonicalHeadersCreate($headers);

    $signatureParts = [
      'Verb' => $verb,
      'Content-Encoding' => $contentEncoding,
      'Content-Language' => $contentLanguage,
      'Content-Length' => $contentLength,
      'Content-MD5' => $contentMD5,
      'Content-Type' => $contentType,
      'Date' => $date,
      'If-Modified-Since' => '',
      'If-Match' => '',
      'If-None-Match' => '',
      'If-Unmodified-Since' => '',
      'Range' => '',
      'CanonicalizedHeaders' => trim($canonicalHeaderString),
      'CanonicalizedResource' => $canonicalResource,
    ];

    return implode(PHP_EOL, array_values($signatureParts));
  }

}
