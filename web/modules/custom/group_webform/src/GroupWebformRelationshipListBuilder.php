<?php

namespace Drupal\group_webform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Controller\GroupRelationshipListBuilder;
use Drupal\group\Entity\GroupRelationshipType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for webform entities in a group.
 */
class GroupWebformRelationshipListBuilder extends GroupRelationshipListBuilder {
  /**
   * The current user's account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RedirectDestinationInterface $redirect_destination, RouteMatchInterface $route_match, EntityTypeInterface $entity_type, AccountInterface $current_user) {
    parent::__construct($entity_type_manager, $redirect_destination, $route_match, $entity_type);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('redirect.destination'),
      $container->get('current_route_match'),
      $entity_type,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $plugin_id = 'group_webform:webform';
    $group_relationship_types = GroupRelationshipType::loadByPluginId($plugin_id);

    // If we have no group webform plugins, we have no group webforms.
    if (empty($group_relationship_types)) {
      return [];
    }

    $query = $this->getStorage()->getQuery();
    $query->accessCheck(TRUE);

    // Filter by group webform plugins.
    $query->condition('type', array_keys($group_relationship_types), 'IN');
    // Only show group relationship for the group on the route.
    $query->condition('gid', $this->group->id());

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    $query->sort($this->entityType->getKey('id'));
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => $this->t('ID'),
      'label' => $this->t('Webform'),
    ];
    $row = $header + parent::buildHeader();
    unset($row['entity_type'], $row['plugin']);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if (empty($entity->getEntity())) {
      return;
    }
    /** @var \Drupal\group\Entity\GroupRelationshipInterface $entity */
    $row['id'] = $entity->id();
    $row['label']['data'] = $entity->getEntity()->toLink(NULL, 'edit-form');
    $row = $row + parent::buildRow($entity);
    unset($row['entity_type'], $row['plugin']);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t("There are no webforms related to this group yet.");
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupRelationshipInterface $entity */
    $operations = parent::getDefaultOperations($entity);
    $plugin_id = 'group_webform:webform';

    // Unset default operations that don't make sense with our UI.
    unset($operations['edit']);
    unset($operations['view']);

    // And rename delete.
    $operations['delete']['title'] = t('Remove from Site');

    // Add operations to edit and delete the actual entity.
    if ($this->group->hasPermission("update any $plugin_id entity", $this->currentUser) || ($this->group->hasPermission("update own $plugin_id entity", $this->currentUser) && $entity->getOwner()->id() == $this->currentUser->id())) {
      $operations['edit-entity'] = [
        'title' => $this->t('Edit'),
        'weight' => 102,
        'url' => $entity->getEntity()->toUrl('edit-form'),
      ];
    }
    if ($this->group->hasPermission("delete any $plugin_id entity", $this->currentUser) || ($this->group->hasPermission("delete own $plugin_id entity", $this->currentUser) && $entity->getOwner()->id() == $this->currentUser->id())) {
      $operations['delete-entity'] = [
        'title' => $this->t('Delete'),
        'weight' => 103,
        'url' => $entity->getEntity()->toUrl('delete-form'),
      ];
    }

    // Slap on redirect destinations for the administrative operations.
    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }

    return $operations;
  }

}
