<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\wa_orange_dam\Service\Api;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a asset_to_orange_dam plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: asset_to_orange_dam
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(id = "asset_to_orange_dam")
 */
final class AssetToOrangeDam extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs the plugin instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly Api $api,
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): mixed {
    if ($value) {
      $return = [];

      if ($result = $this->api->search(['query' => 'image-id-(asset-bank).Image-ID-(Asset-Bank):' . $value])) {
        if (!isset($result['APIResponse']['Items'][0]['SystemIdentifier'])) {

          // If we don't get it by the asset bank id, we'll try searching for
          // the value in the filename. Currently this search returns no
          // results.
          $result = $this->api->search(['query' => 'CoreField.OriginalFileName:*' . $value . '*']);

          if (!empty($result['APIResponse']['Items']) && count($result['APIResponse']['Items']) > 1) {

            // We have no way of deduping the wildcard searches, so if we have
            // more than one result, we'll have to skip them.
            return NULL;
          }
        }

        // If there is more than one asset with the same id, we're just going to use the first.
        if (isset($result['APIResponse']['Items'][0]['SystemIdentifier'])) {
          $return['system_identifier'] = $result['APIResponse']['Items'][0]['SystemIdentifier'];

          if (isset($result['APIResponse']['Items'][0]['path_TR1'])) {
            foreach (['Width', 'Height'] as $key) {
              if (isset($result['APIResponse']['Items'][0]['path_TR1'][$key])) {
                $return[strtolower($key)] = $result['APIResponse']['Items'][0]['path_TR1'][$key];
              }
            }
          }
        }
      }

      return empty($return) ? NULL : $return;
    }
    else {
      return NULL;
    }
  }

}
