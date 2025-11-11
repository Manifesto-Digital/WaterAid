<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * The 'document_source' source plugin.
 *
 * @MigrateSource(
 *   id = "document_source",
 *   source_module = "wa_migration",
 * )
 */
final class DocumentSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    $query = $this->select('media__field_document', 'm')
      ->fields('m');

    $query->leftJoin('file_managed', 'f', 'f.fid = m.field_document_target_id');
    $query->addField('f', 'filename');

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
      'field_document_target_id' => $this->t('The file id'),
      'filename' => $this->t('The file name'),
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
