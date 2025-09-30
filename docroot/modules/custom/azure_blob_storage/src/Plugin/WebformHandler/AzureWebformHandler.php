<?php

namespace Drupal\azure_blob_storage\Plugin\WebformHandler;

use Drupal\Core\Queue\QueueInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "azure_blob_storage_handler",
 *   label = @Translation("Azure Blob Storage"),
 *   category = @Translation("azure"),
 *   description = @Translation("Transfers webform submissions to the Azure Blob storage."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class AzureWebformHandler extends WebformHandlerBase {

  /**
   * The 'azure_blob_storage_queue'.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  private readonly QueueInterface $queue;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): AzureWebformHandler {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->queue = $container->get('queue')->get('azure_blob_storage_queue');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE): void {

    // Add the submission data to the queue so it can be restructured and pushed
    // to the Azure Blob Storage in a separate process that won't slow down the
    // confirmation page displaying.
    $item = [
      'webform_id' => $webform_submission->getWebform()->id(),
      'sid' => $webform_submission->id(),
    ];

    $this->queue->createItem($item);
  }

}
