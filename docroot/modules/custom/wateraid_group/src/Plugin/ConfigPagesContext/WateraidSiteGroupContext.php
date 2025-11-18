<?php

namespace Drupal\wateraid_group\Plugin\ConfigPagesContext;

use Drupal\config_pages\ConfigPagesContextBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a group context for config pages.
 *
 * @ConfigPagesContext(
 *   id = "wateraid_site_group",
 *   label = @Translation("WaterAid site group"),
 * )
 */
class WateraidSiteGroupContext extends ConfigPagesContextBase {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Main request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  const GROUP_ID_QUERY_PARAM = 'wateraid_site_gid';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->routeMatch = $container->get('current_route_match');
    $instance->request = $container->get('request_stack')->getMainRequest();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    $group = $this->getGroup();
    if ($group && $group->bundle() === 'wateraid_site') {
      return $group->label();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $group = $this->getGroup();
    if ($group && $group->bundle() === 'wateraid_site') {
      return $group->id();
    }
    return NULL;
  }

  /**
   * Get the current group entity from the route.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The group entity or NULL.
   */
  protected function getGroup() {
    // Try to get group from route parameter.
    $group = $this->routeMatch->getParameter('group');
    if ($group instanceof GroupInterface) {
      return $group;
    }

    // If there is no group, we might be in the config pages add/edit forms,
    // where instead we append the group as a query string.
    $route_name = $this->routeMatch->getRouteName();
    if (str_starts_with($route_name, 'config_pages.')) {
      $group_id = $this->request->query->get(self::GROUP_ID_QUERY_PARAM);
      if ($group_id) {
        /** @var \Drupal\group\Entity\GroupInterface $group */
        $group = $this->entityTypeManager->getStorage('group')->load($group_id);
        return $group instanceof GroupInterface ? $group : NULL;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinks() {
    $links = [];
    $value = $this->getValue();
    \Drupal::service('entity_type.manager')->getStorage('group')->resetCache();
    /** @var \Drupal\group\Entity\GroupInterface[] $groups */
    $groups = \Drupal::service('entity_type.manager')->getStorage('group')->loadByProperties(['type' => 'wateraid_site']);
    foreach ($groups as $group) {
      $links[] = [
        'title' => $group->label(),
        'href' => Url::fromRoute('<current>', [], ['query' => [self::GROUP_ID_QUERY_PARAM => $group->id()]]),
        'selected' => $value == $group->id(),
        'value' => $group->id(),
      ];
    }
    return $links;
  }

}
