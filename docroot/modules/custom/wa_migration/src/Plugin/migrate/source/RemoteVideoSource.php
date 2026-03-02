<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * The 'remote_video_source' source plugin.
 *
 * @MigrateSource(
 *   id = "remote_video_source",
 *   source_module = "wa_migration",
 * )
 */
final class RemoteVideoSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    $query = $this->select('media__field_media_video_embed_field', 'm')
      ->fields('m');

    $settings = $this->migration->getPluginDefinition();
    $migration_group = $settings['migration_group'] ?? NULL;

    // If this is Washmatters, only bring in English content.
    if (str_starts_with($migration_group, 'wateraid_wash')) {
      $query->condition('langcode', 'en');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [
      'entity_id' => $this->t('The Media ID'),
      'langcode' => $this->t('The language'),
      'bundle' => $this->t('The bundle'),
      'field_media_video_embed_field_value' => $this->t('The video URL'),
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
