<?php

declare(strict_types=1);

namespace Drupal\wa_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The 'user_source' source plugin.
 *
 * @MigrateSource(
 *   id = "user_source",
 *   source_module = "wa_migration",
 * )
 */
final class UserSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    StateInterface $state,
    private readonly PasswordGeneratorInterface $passwordGenerator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state'),
      $container->get('password_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    $query = $this->select('users_field_data', 'u')
      ->fields('u')
      ->condition('u.uid', 1, '>')
      ->condition('u.status', 1);

    // Exclude any accounts for agencies looking after the old site.
    $and = $query->andConditionGroup();
    $and->condition('u.name', '%access%', 'NOT LIKE');
    $and->condition('u.name', '%@manifesto.co.uk%', 'NOT LIKE');
    $query->condition($and);

    $query->leftJoin('user__field_real_name', 'r', 'r.entity_id = u.uid');
    $query->fields('r', ['field_real_name_value']);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [
      'name' => $this->t('The User Name'),
      'langcode' => $this->t('The language'),
      'mail' => $this->t('The User email'),
      'pass' => $this->t('The user password'),
      'timezone' => $this->t('The User TimeZone'),
      'status' => $this->t('The user status'),
      'field_real_name_value' => $this->t('The user real name'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    $ids['uid'] = [
      'type' => 'integer',
    ];

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row): bool {

    // Create a new random password.
    $row->setSourceProperty('pass', $this->passwordGenerator->generate());

    return parent::prepareRow($row);
  }

}
