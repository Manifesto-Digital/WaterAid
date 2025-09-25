<?php

namespace Drupal\wateraid_azure_storage\Exception;

/**
 * An exception thrown for a handler that is missing or disabled.
 */
class DisabledHandlerException extends BaseAzureStorageQueueException {

  /**
   * {@inheritdoc}
   */
  protected bool $silence = TRUE;

}
