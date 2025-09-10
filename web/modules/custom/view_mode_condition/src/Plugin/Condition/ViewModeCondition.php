<?php

namespace Drupal\view_mode_condition\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Entity View Mode' condition.
 *
 * @todo Currently this only supports nodes, make it support all entities.
 *
 * @Condition(
 *   id = "view_mode_condition",
 *   label = @Translation("View mode"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class ViewModeCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   */
  protected EntityDisplayRepositoryInterface $entityDisplayRepository;


  /**
   * The entity storage.
   */
  protected EntityStorageInterface $entityStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager')->getStorage('entity_view_mode')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityDisplayRepositoryInterface $entity_display_repository, EntityStorageInterface $entity_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    if (empty($this->configuration['view_modes']) && !$this->isNegated()) {
      return TRUE;
    }

    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getContextValue('node');
    /** @var \Drupal\node\Entity\NodeType $bundle */
    $view_modes = $this->entityDisplayRepository->getViewModeOptionsByBundle($node->getEntityTypeId(), $node->bundle());

    foreach ($this->configuration['view_modes'] as $view_mode) {
      if (isset($view_modes[$view_mode])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return ['view_modes' => []] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $options = [];
    $entity_view_modes = $this->entityStorage->loadMultiple();
    /** @var \Drupal\Core\Entity\Entity\EntityViewMode $type */
    foreach ($entity_view_modes as $type) {
      // @todo Support other entity types.
      if ($type->getTargetType() === 'node') {
        // Can't store dots inside config.
        $options[substr($type->id(), strpos($type->id(), '.') + 1)] = $type->label();
      }
    }
    $form['view_modes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Show only when the entity has the following view modes.'),
      '#options' => $options,
      '#default_value' => $this->configuration['view_modes'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['view_modes'] = array_filter($form_state->getValue('view_modes'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (count($this->configuration['view_modes']) > 1) {
      $view_modes = $this->configuration['view_modes'];
      $last = array_pop($view_modes);
      $view_modes = implode(', ', $view_modes);
      return $this->t('The view mode is @view_modes or @last',
        ['@view_modes' => $view_modes, '@last' => $last]);
    }
    $view_mode = reset($this->configuration['view_modes']);
    return $this->t('The view mode is @view_mode', ['@view_mode' => $view_mode]);
  }

}
