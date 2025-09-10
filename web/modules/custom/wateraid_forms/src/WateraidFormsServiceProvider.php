<?php

namespace Drupal\wateraid_forms;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Wateraid Forms Service Provider.
 *
 * @package Drupal\wateraid_forms
 */
class WateraidFormsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    // Overrides language_manager class to test domain language negotiation.
    $definition = $container->getDefinition('webform_submission.exporter');
    $definition->setClass(WateraidWebformSubmissionExporter::class);
  }

}
