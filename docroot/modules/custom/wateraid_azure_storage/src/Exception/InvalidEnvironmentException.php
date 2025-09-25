<?php

namespace Drupal\wateraid_azure_storage\Exception;

/**
 * An exception thrown for an invalid environment usage.
 */
class InvalidEnvironmentException extends BaseAzureStorageQueueException {

  /**
   * {@inheritdoc}
   */
  protected bool $silence = TRUE;

}
