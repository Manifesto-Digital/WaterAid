<?php

declare(strict_types=1);

namespace Drupal\azure_blob_storage\Service;

use Drupal\Core\Site\Settings;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

/**
 * Service for connecting to Azure Blob Storage.
 */
final class AzureApi {

  /**
   * The API version to use.
   *
   * @var string
   */
  private string $apiVersion = "2019-12-12";

  /**
   * The Blob storage account name to use.
   *
   * @var string
   */
  private string $accountName = 'watestcrmlocation';

  /**
   * Gets the private key from settings.
   *
   * @return string
   *   The shared key.
   */
  private function sharedKey(): string {
    return Settings::get('azure_blob_storage');
  }

  /**
   * Puts data into an Azure Blob.
   *
   * @param string $blob_name
   *   The Blob to push data into.
   * @param string $data
   *   The data to store in the blob.
   *
   * @return bool
   *   Whether the data was successfully pushed to the blob.
   */
  public function putBlob(string $blob_name, string $data): bool {
    $result = FALSE;

    if (!$key = $this->sharedKey()) {
      return $result;
    }

    try {
      $blobClient = BlobRestProxy::createBlobService("DefaultEndpointsProtocol=https;AccountName=watestcrmlocation;AccountKey=$key;EndpointSuffix=core.windows.net");

      if ($blobClient->createBlockBlob('wateraid-webforms', $blob_name, $data)) {
        $result = TRUE;
      }
    }
    catch (\Exception $e) {

      // We only want to record failures after 5 retries, so we won't log this
      // here and will instead rely on the queue to do the failure logging.
    }

    return $result;
  }

  /**
   * Send a blob to the blob storage.
   *
   * @param string $container
   *   The container to use when storing the blob.
   * @param string $blobName
   *   The name of the blob.
   * @param string $blobData
   *   The blob content.
   *
   * @return void
   * @throws \Exception
   */
  function blobPut($container, $blobName, $blobData) {
    $contentType = "application/json";
    $date = $this->dateGet();
    $httpMethod = "PUT";
    $xmsHeaders = [
      "x-ms-version"   => $this->apiVersion,
      "x-ms-blob-type" => "BlockBlob",
    ];

    $contentMD5 = base64_encode(md5($blobData, TRUE));

    $signature = $this->signatureCreate(
      $httpMethod,
      "",
      "",
      strlen($blobData),
      $contentMD5,
      $contentType,
      $date,
      $xmsHeaders,
      "/$this->accountName/$container/$blobName"
    );

    $encodedSignature = base64_encode(
      hash_hmac(
        "sha256",
        utf8_encode($signature),
        base64_decode($this->sharedKey()),
        TRUE
      )
    );

    $headers = $this->headersMerge(
      [
        "Authorization" => "SharedKey $this->accountName:$encodedSignature",
        "Content-MD5"   => $contentMD5,
        "Content-Type"  => $contentType,
        "Date"          => $date,
      ],
      $xmsHeaders
    );

    $url = "https://$this->accountName.blob.core.windows.net/$container/$blobName";

    $this->requestSend(
      $url,
      $httpMethod,
      $headers,
      $blobData
    );
  }

  /**
   * Generate the canonical headers.
   *
   * @param array $headers
   *   An array of header data.
   *
   * @return string
   */
  function canonicalHeadersCreate($headers) {
    $canonicalHeaderString = "";

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
   */
  function dateGet() {
    $date = trim(date("D, d M Y H:i:s T"));
    return str_replace("BST", "GMT", $date);
  }

  /**
   * Merge all header arrays.
   *
   * @param ...$args
   *
   * @return array
   */
  private function headersMerge(...$args): array {
    $headers = [];

    foreach ($args as $arraySet) {
      foreach ($arraySet as $key => $value) {
        $headers[] = "$key: $value";
      }
    }

    return $headers;
  }

  function requestSend($url, $method, $headers, $data) {
    $curl= curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($curl);

    if ($this->debug) {
      print_r($response);
    }

    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_code >= 200 && $http_code < 300) {
      echo "Blob sent successfully" . PHP_EOL;
    }
    else {
      throw new \Exception("Unexpected HTTP code:  $http_code");
    }
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
   * @param string $contentLength
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
      "Verb"                  => $verb,
      "Content-Encoding"      => $contentEncoding,
      "Content-Language"      => $contentLanguage,
      "Content-Length"        => $contentLength,
      "Content-MD5"           => $contentMD5,
      "Content-Type"          => $contentType,
      "Date"                  => $date,
      "If-Modified-Since"     => "",
      "If-Match"              => "",
      "If-None-Match"         => "",
      "If-Unmodified-Since"   => "",
      "Range"                 => "",
      "CanonicalizedHeaders"  => trim($canonicalHeaderString),
      "CanonicalizedResource" => $canonicalResource,
    ];

    return implode(PHP_EOL, array_values($signatureParts));
  }

}
