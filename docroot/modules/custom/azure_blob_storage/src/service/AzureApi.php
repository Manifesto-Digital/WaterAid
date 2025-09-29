<?php

declare(strict_types=1);

namespace Drupal\azure_blob_storage\service;

use Drupal\Core\Site\Settings;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

/**
 * Service for connecting to Azure Blob Storage.
 */
final class AzureApi {

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

    if (!$key = Settings::get('azure_blob_storage')) {
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

}
