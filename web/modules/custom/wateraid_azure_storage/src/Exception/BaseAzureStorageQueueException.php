<?php

namespace Drupal\wateraid_azure_storage\Exception;

/**
 * A base exception thrown for anything related to Azure Storage Queues.
 */
abstract class BaseAzureStorageQueueException extends \RuntimeException {

  /**
   * Indicates whether the Exception is to be silenced.
   */
  protected bool $silence = FALSE;

  /**
   * Get the silenced setting.
   *
   * Silenced Exceptions are to be used for identification of an action for
   * graceful shutdown with logging but without re-attempting later.
   */
  public function isSilenced(): bool {
    return $this->silence;
  }

}
