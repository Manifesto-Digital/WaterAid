<?php

namespace Drupal\wateraid_azure_storage;

use Drupal\Component\Serialization\Json;

/**
 * Azure Stotrage Queue Message.
 *
 * @package Drupal\wateraid_azure_storage
 */
class AzureStorageQueueMessage {

  /**
   * Id.
   *
   * @var int
   *   Id of Webform Submission.
   */
  protected int $id;

  /**
   * Data array.
   *
   * @var mixed[]
   *   Array of Webform Submission data.
   */
  protected array $data;

  /**
   * Sets the Webform Submission Id.
   *
   * @param int $id
   *   Webform Submission Id.
   *
   * @return $this
   */
  public function setId(int $id): static {
    $this->id = $id;
    return $this;
  }

  /**
   * Gets the Webform Submission Id.
   *
   * @return int
   *   Webform Submission Id.
   */
  public function getId(): int {
    return $this->id;
  }

  /**
   * Sets the Webform Submission Id.
   *
   * @param mixed[] $data
   *   Webform Submission Data.
   *
   * @return $this
   */
  public function setData(array $data) {
    $this->data = $data;
    return $this;
  }

  /**
   * Get Message data.
   *
   * @param string $format
   *   Return as JSON optional.
   *
   * @return mixed
   *   Webform Submission Data.
   */
  public function getData(string $format = 'json'): mixed {
    return $format === 'json' ? Json::encode($this->data) : $this->data;
  }

}
