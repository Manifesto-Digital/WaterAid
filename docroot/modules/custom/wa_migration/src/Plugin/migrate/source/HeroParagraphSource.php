<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Row;

/**
 * The 'hero_paragraph_source' source plugin.
 *
 * @MigrateSource(
 *   id = "hero_paragraph_source",
 *   source_module = "wa_migration",
 * )
 */
final class HeroParagraphSource extends ParagraphSource {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    return $this->select('paragraphs_item_field_data', 'p')
      ->fields('p')
      ->condition('p.type', 'hero');
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    $return = parent::fields();

    $return['hero_type'] = $this->t('The hero type this hero ports to');

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row): bool {
    parent::prepareRow($row);

    $value = 'hero_basic';

    if ($row->getSourceProperty('field_hero_video')) {
      $value = 'hero_video';
    }
    elseif ($row->getSourceProperty('field_hero_image')) {
      $value = 'hero_image';
    }

    $row->setSourceProperty('hero_type', $value);

    return TRUE;
  }

}
