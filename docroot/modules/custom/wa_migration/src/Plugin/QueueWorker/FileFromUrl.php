<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\wa_orange_dam\Service\Api;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'wa_migration_washmatters_files' queue worker.
 *
 * @QueueWorker(
 *   id = "wa_migration_washmatters_files",
 *   title = @Translation("Washmatters Files"),
 * )
 */
final class FileFromUrl extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new FileFromUrl instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly Api $api,
    private readonly LoggerChannelFactory $loggerChannelFactory,
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
      $container->get('wa_orange_dam.api'),
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if ($data) {
      $error = TRUE;

      if ($return = $this->api->createFile($data, 'WI11SF0L')) {
        if (isset($return['isSuccess'])) {
          $error = FALSE;
        }
      }

      if ($error) {
        $this->loggerChannelFactory->get('wa_migration')->error($this->t('Error transferring file from :url', [
          ':url' => $data,
        ]));
      }
    }
  }

}
