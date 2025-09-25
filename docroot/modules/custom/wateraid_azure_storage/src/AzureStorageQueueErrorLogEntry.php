<?php

namespace Drupal\wateraid_azure_storage;

/**
 * Error log entry.
 *
 * @package Drupal\wateraid_azure_storage
 */
class AzureStorageQueueErrorLogEntry {

  /**
   * Message string.
   *
   * @var string
   *   A message.
   */
  protected string $message;

  /**
   * Time integer.
   *
   * @var int
   *   A timestamp.
   */
  protected int $timestamp;

  /**
   * Counter.
   *
   * @var int
   *   Re-attempt count.
   */
  protected int $retryCount;

  /**
   * Sets the message.
   *
   * @param string $message
   *   Message as string.
   *
   * @return $this
   */
  public function setMessage(string $message): static {
    $this->message = $message;
    return $this;
  }

  /**
   * Sets the timestamp.
   *
   * @param int $timestamp
   *   Timestamp as integer.
   *
   * @return $this
   */
  public function setTimestamp(int $timestamp): static {
    $this->timestamp = $timestamp;
    return $this;
  }

  /**
   * Sets the re-attempt / retry count.
   *
   * @param int $retry_count
   *   Retry count as integer.
   *
   * @return $this
   */
  public function setRetryCount(int $retry_count): static {
    $this->retryCount = $retry_count;
    return $this;
  }

  /**
   * Get message.
   *
   * @return string
   *   Message as a string.
   */
  public function getMessage(): string {
    return $this->message;
  }

  /**
   * Get timestamp.
   *
   * @return int
   *   Timestamp as an integer.
   */
  public function getTimestamp(): int {
    return $this->timestamp;
  }

  /**
   * Get retry count.
   *
   * @return int
   *   Retry count as an integer.
   */
  public function getRetryCount(): int {
    return $this->retryCount;
  }

}
