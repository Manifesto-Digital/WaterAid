<?php

namespace Drupal\wateraid_azure_storage;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform Service interface.
 *
 * @package Drupal\wateraid_azure_storage
 */
interface AzureStorageQueueWebformServiceInterface {

  /**
   * POST the Webform Submission details to Azure.
   *
   * This service is an intermediate.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission interface.
   * @param string $process_type
   *   A string indicating the type of process. Can be either 1 of:
   *   - "initial" For initial attempts i.e. AzureStorageQueueWebformHandler.
   *   - "queue" For automated retries on a queue i.e. AzureStorageQueueWorker.
   *   - "manual" For manual retries i.e. VBO AzureStorageQueueAction.
   *
   * @throws \Drupal\wateraid_azure_storage\Exception\DisabledHandlerException
   *   When the handler is either disabled or missing.
   * @throws \Drupal\wateraid_azure_storage\Exception\InvalidEnvironmentException
   *   When the environment is not equal to the queue mode identifier.
   * @throws \MicrosoftAzure\Storage\Common\Exceptions\ServiceException
   *   When the Azure Storage Queue fails validation.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   When the Webform Submission fails to save.
   */
  public function postWebformSubmission(WebformSubmissionInterface $webform_submission, string $process_type = 'initial'): void;

  /**
   * Validates the Azure Storage Queue name.
   *
   * @param string $queue_name
   *   The Azure Storage Queue name string.
   *
   * @throws \Drupal\wateraid_azure_storage\Exception\InvalidEnvironmentException
   *   When the environment is not equal to the queue mode identifier.
   */
  public function queueModeValidate(string $queue_name): void;

  /**
   * Get the current mode.
   *
   * The result string is either "live" or "test" and is based on both the
   * configuration mode of Azure Storage and the environment variables.
   */
  public function getMode(): string;

  /**
   * Get the current env mode.
   *
   * The result string is either "live" or "test" and is based only on the
   * environment variables.
   */
  public function getEnvMode(): string;

}
