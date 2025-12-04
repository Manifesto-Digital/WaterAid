<?php

namespace Drupal\wa_migration\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityInterface;
use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\user\Plugin\migrate\destination\EntityUser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a destination plugin for migrating user entities.
 *
 * Example:
 *
 * The example below migrates users and preserves original passwords from a
 * source that has passwords as MD5 hashes without salt. The passwords will be
 * salted and re-hashed before they are saved to the destination Drupal
 * database. The MD5 hash used in the example is a hash of 'password'.
 *
 * The example uses the EmbeddedDataSource source plugin for the sake of
 * simplicity. The mapping between old user_ids and new Drupal uids is saved in
 * the migration map table.
 * @code
 * id: custom_user_migration
 * label: Custom user migration
 * source:
 *   plugin: embedded_data
 *   data_rows:
 *     -
 *       user_id: 1
 *       name: JohnSmith
 *       mail: johnsmith@example.com
 *       hash: '5f4dcc3b5aa765d61d8327deb882cf99'
 *   ids:
 *     user_id:
 *       type: integer
 * process:
 *   name: name
 *   mail: mail
 *   pass: hash
 *   status:
 *     plugin: default_value
 *     default_value: 1
 * destination:
 *   plugin: entity:user
 *   md5_passwords: true
 * @endcode
 *
 * For configuration options inherited from the parent class, refer to
 * \Drupal\migrate\Plugin\migrate\destination\EntityContentBase.
 *
 * The example above is about migrating an MD5 password hash. For more examples
 * on different password hash types and a list of other user properties, refer
 * to the handbook documentation:
 * @see https://www.drupal.org/docs/8/api/migrate-api/migrate-destination-plugins-examples/migrating-users
 */
#[MigrateDestination('wa_user:user')]
class WaEntityUser extends EntityUser {


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    $entity_type = 'user';
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')->getStorage($entity_type),
      array_keys($container->get('entity_type.bundle.info')->getBundleInfo($entity_type)),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('password'),
      $container->get('account_switcher')
    );
  }

  /**
   * Creates or loads an entity.
   *
   * @param \Drupal\migrate\Row $row
   *   The row object.
   * @param array $old_destination_id_values
   *   The old destination IDs.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity we are importing into.
   */
  protected function getEntity(Row $row, array $old_destination_id_values): EntityInterface {

    // If we already have imported this user, just use their account.
    if ($mail = $row->getSourceProperty('mail')) {
      if ($user = $this->storage->loadByProperties([
        'mail' => $mail,
      ])) {
        return reset($user);
      }
    }

    return parent::getEntity($row, $old_destination_id_values);
  }

}
