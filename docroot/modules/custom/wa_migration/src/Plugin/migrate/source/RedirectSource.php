<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * The 'redirect_source' source plugin.
 *
 * @MigrateSource(
 *   id = "redirect_source",
 *   source_module = "wa_migration",
 * )
 */
class RedirectSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    $query = $this->select('redirect', 'r')
      ->fields('r');

    $settings = $this->migration->getPluginDefinition();
    $migration_group = $settings['migration_group'] ?? NULL;

    // If this is Washmatters, only bring in English content.
    if (str_starts_with($migration_group, 'wateraid_wash')) {
      $query->condition('r.langcode', 'en');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [
      'language' => $this->t('The language'),
      'redirect_source__path' => $this->t('The source'),
      'redirect_redirect__uri' => $this->t('The destination'),
      'uid' => $this->t('The creating user'),
      'rid' => $this->t('The unique id'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    $ids['rid'] = [
      'type' => 'integer',
    ];

    return $ids;
  }

}
