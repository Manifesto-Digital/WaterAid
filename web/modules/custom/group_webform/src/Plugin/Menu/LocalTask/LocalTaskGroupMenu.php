<?php

namespace Drupal\group_webform\Plugin\Menu\LocalTask;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides the local task handler for Group Menus.
 */
class LocalTaskGroupMenu extends LocalTaskDefault implements ContainerFactoryPluginInterface {

  /**
   * Constructs a Drupal object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $route_parameters = parent::getRouteParameters($route_match);

    // If we don't have a group menu, we should be able to load it from the
    // group. If we don't have a group, there's nothing we can do.
    if (!isset($route_parameters['group_content_menu']) && isset($route_parameters['group'])) {

      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = $this->entity_type_manager->getStorage('group')->load($route_parameters['group']);

      if ($related = $group->getRelatedEntities('group_content_menu:wateraid_site_menu')) {

        // We can assume that every group only has one menu.
        if ($menu = reset($related)) {
          $route_parameters['group_content_menu'] = $menu->id();
        }
      }
    }

    return $route_parameters;
  }

}
