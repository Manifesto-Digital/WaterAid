<?php

declare(strict_types=1);

namespace Drupal\azure_blob_storage\service;

use Psr\Http\Client\ClientInterface;

/**
 * Service for connecting to Azure Blob Storage.
 */
final class AzureApi {

  /**
   * Constructs an AzureApi object.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
  ) {}

  /**
   * Puts data into an Azure Blob.
   *
   * @param string $blob_name
   *   The Blob to push data into.
   * @param mixed $data
   *   The data to store in the blob.
   */
  public function putBlob(string $blob_name, mixed $data): void {
    // @todo Place your code here.
  }

  /**
   * The blob storage URL.
   *
   * @var string
   */
  private string $url = 'https://myaccount.blob.core.windows.net/mycontainer/';

  /**
   * Helper to override the base URL.
   *
   * @param string $url
   *   The new URL to use.
   */
  public function setUrl(string $url): void {
    $this->url = $url;
  }

  /**
   * Helper to create the blob URL.
   *
   * @param string $blob_name
   *   The name of the blob to push to.
   *
   * @return string
   *   The URL including the blob parameter.
   */
  private function getUrl(string $blob_name): string {
    return $this->url . $blob_name;
  }

}
