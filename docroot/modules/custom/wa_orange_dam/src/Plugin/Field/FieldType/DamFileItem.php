<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\wa_orange_dam\Service\Api;

/**
 * Defines the 'wa_orange_dam_file' field type.
 *
 * @FieldType(
 *   id = "wa_orange_dam_file",
 *   label = @Translation("Orange DAM File"),
 *   description = @Translation("Files from the Orange DAM"),
 *   default_widget = "wa_orange_dam",
 *   default_formatter = "wa_orange_dam_file_formater",
 * )
 */
class DamFileItem extends FieldItemBase {

  /**
   * Helper to return the API service.
   *
   * @return Api
   *   The API service.
   */
  protected function api(): Api {
    return \Drupal::service('wa_orange_dam.api');
  }

  /**
   * Helper to get data about the file from the API.
   *
   * @return array
   *   An array of data about the actual file.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getDownloadInfo(): array {
    $data = [];

    if ($system_id = $this->get('system_identifier')->getString()) {
      $public_link = $this->api()->getPublicLink($system_id);
      $search = $this->api()
        ->search(['query' => 'SystemIdentifier:' . $system_id], [
          'CoreField.FileSize',
          'Document.FileSizeMB',
          'Document.FileExtension',
          'Document.MimeType',
        ]);

      $data['url'] = $public_link['link'] ?? '';
      $data['filename'] = (isset($public_link['link'])) ? basename($public_link['link']) : '';
      $data['file_size'] = $search['APIResponse']['Items'][0]['CoreField.FileSize'] ?? '';
      $data['file_size_mb'] = $search['APIResponse']['Items'][0]['Document.FileSizeMB'] ?? '';
      $data['file_mimetype'] = $search['APIResponse']['Items'][0]['MIMEtype'] ?? '';
      $data['file_extension'] = $search['APIResponse']['Items'][0]['Document.FileExtension'] ?? '';
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    return match ($this->get('system_identifier')->getValue()) {
      NULL, '' => TRUE,
      default => FALSE,
    };
  }

  /**
   * Allows image formater to be run as if it is an entity reference.
   *
   * @return false
   *   Never has an entity attached, so always false.
   */
  public function hasNewEntity(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['system_identifier'] = DataDefinition::create('string')
      ->setLabel(t('Orange Dam System Identifier'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    $columns = [
      'system_identifier' => [
        'type' => 'varchar',
        'not null' => FALSE,
        'description' => 'Orange Dam System Identifier.',
        'length' => 255,
      ],
    ];

    return [
      'columns' => $columns,
    ];
  }

}
