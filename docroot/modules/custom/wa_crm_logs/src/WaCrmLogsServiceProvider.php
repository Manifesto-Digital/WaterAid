<?php

declare(strict_types=1);

namespace Drupal\wa_crm_logs;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Defines a service provider for the Wa crm logs module.
 */
final class WaCrmLogsServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {

    // Add the service dependency here so we can install the new module without
    // any errors.
    if ($container->hasDefinition('azure_blob_storage.queue_handler')) {
      $container->getDefinition('azure_blob_storage.queue_handler')
        ->addArgument(new Reference('wa_crm_logs.logging'));
    }
  }

}
