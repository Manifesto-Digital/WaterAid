<?php

namespace Drupal\wateraid_azure_storage;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Message Builder Interface.
 *
 * @package Drupal\wateraid_azure_storage
 */
interface AzureStorageQueueMessageBuilderInterface {

  /**
   * Builds a message.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform Submission.
   * @param mixed[] $config
   *   Optional Webform handler config.
   *
   * @return \Drupal\wateraid_azure_storage\AzureStorageQueueMessage
   *   An AzureStorageQueueMessage object.
   */
  public function create(WebformSubmissionInterface $webform_submission, array $config = []): AzureStorageQueueMessage;

}
