<?php

namespace Drupal\group_webform\Controller;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\group\Entity\Controller\GroupRelationshipController;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for 'group_webform' GroupRelationship routes.
 */
class GroupWebformController extends GroupRelationshipController {
  /**
   * The private store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $groupRelationTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new GroupRelationshipController.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The private store factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $groupRelationTypeManager
   *   The group relation type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, GroupRelationTypeManagerInterface $groupRelationTypeManager, RendererInterface $renderer) {
    $this->privateTempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->groupRelationTypeManager = $groupRelationTypeManager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('group_relation_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Provides the group webform overview page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group whose group webform relationships are being shown.
   *
   * @return array
   *   A render array as expected by drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function groupRelationshipOverview(GroupInterface $group) {
    $class = '\Drupal\group_webform\GroupWebformRelationshipListBuilder';
    $definition = $this->entityTypeManager
      ->getDefinition('group_relationship');
    return $this->entityTypeManager
      ->createHandlerInstance($class, $definition)
      ->render();
  }

  /**
   * Provides the group webform overview page title.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to show the group webform relationship for.
   *
   * @return string
   *   The page title for the group webform overview page.
   */
  public function groupRelationshipOverviewTitle(GroupInterface $group) {
    return $this->t("%label webforms", ['%label' => $group->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function addPage(GroupInterface $group, $create_mode = FALSE, $base_plugin_id = NULL) {
    $build = parent::addPage($group, $create_mode, $base_plugin_id);
    // Do not interfere with redirects.
    if (!is_array($build)) {
      return $build;
    }

    // Retrieve all responsible group relationship types, keyed by plugin ID.
    foreach ($this->addPageBundles($group, $create_mode, $base_plugin_id) as $bundle_name) {
      /** @var \Drupal\group\Entity\GroupRelationshipTypeInterface $group_relationship_type */
      if (!empty($build['#bundles'][$bundle_name])) {
        $build['#bundles'][$bundle_name]['label'] = $this->t('Webform');
        $build['#bundles'][$bundle_name]['description'] = $this->t('Create a webform for the group.');
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function addPageBundles(GroupInterface $group, $create_mode, $base_plugin_id) {
    $relationship_types = parent::addPageBundles($group, $create_mode, $base_plugin_id);
    $return_types = [];

    // Filter the full list to just group_webform items.
    foreach ($relationship_types as $key => $type) {
      if ($type->getPluginId() === 'group_webform:webform') {
        $return_types[$key] = $type;
      }
    }

    return $return_types;
  }

}
