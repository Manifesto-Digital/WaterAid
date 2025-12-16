<?php

declare(strict_types=1);

namespace Drupal\azure_blob_storage\Plugin\QueueWorker;

use Drupal\azure_blob_storage\service\AzureApi;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'azure_blob_storage_queue' queue worker.
 *
 * @QueueWorker(
 *   id = "azure_blob_storage_queue",
 *   title = @Translation("Blob Storage Queue")
 * )
 */
final class BlobStorageQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  private array $optionList = [];

  /**
   * Constructs a new BlobStorageQueue instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AzureApi $azureBlobStorageApi,
    private readonly LoggerChannelInterface $loggerChannel,
    private readonly QueueInterface $queue,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('azure_blob_storage.api'),
      $container->get('logger.channel.azure_blob_storage'),
      $container->get('queue')->get('azure_blob_storage_queue'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {

  }
}
