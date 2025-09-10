<?php

namespace Drupal\wateraid_base_core\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Engagement Tool Node' condition.
 *
 * @Condition(
 *   id = "engagement_tool_node",
 *   label = @Translation("Engagement Tool Node"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"), required = FALSE),
 *   }
 * )
 */
class EngagementToolNode extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    /** @var \Drupal\node\NodeInterface $node */
    if ($node = $this->getContextValue('node')) {
      return $node->getType() === 'focused_engagement_tool';
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // @todo Implement summary() method.
  }

}
