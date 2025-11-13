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
    return $this->select('redirect', 'r')
      ->fields('r');
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
