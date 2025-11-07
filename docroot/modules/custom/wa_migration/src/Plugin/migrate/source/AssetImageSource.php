<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * The 'asset_image_source' source plugin.
 *
 * @MigrateSource(
 *   id = "asset_image_source",
 *   source_module = "wa_migration",
 * )
 */
final class AssetImageSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    return $this->select('media__field_remote_id', 'm')
      ->fields('m');
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [
      'entity_id' => $this->t('The Media ID'),
      'langcode' => $this->t('The language'),
      'bundle' => $this->t('The bundle'),
      'field_remote_id_value' => $this->t('The remote id to search with'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    $ids['entity_id'] = [
      'type' => 'integer',
    ];

    return $ids;
  }

}
