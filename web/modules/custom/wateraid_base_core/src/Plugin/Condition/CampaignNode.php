<?php

namespace Drupal\wateraid_base_core\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Campaign Node' condition.
 *
 * @Condition(
 *   id = "campaign_node",
 *   label = @Translation("Campaign Node"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"), required = FALSE),
 *   }
 * )
 */
class CampaignNode extends ConditionPluginBase implements ContainerFactoryPluginInterface {

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
    $node = $this->getContextValue('node');
    // Make sure context is returning the node value.
    if ($node) {
      $node_type = $node->getType();
      if ($node_type === 'campaign') {
        return TRUE;
      }
      if ($node_type === 'flexible_content' || $node_type === 'donation_landing_page' || $node_type === 'focused_engagement_tool' || $node_type === 'standard_page') {
        // Checking the header style field is set for minimal header.
        if ($node->hasField('field_header_style')
          && !empty($node->get('field_header_style'))
          && isset($node->get('field_header_style')->getValue()[0]['value'])
          && $node->get('field_header_style')->getValue()[0]['value'] === '1') {
          return TRUE;
        }
      }
      else {
        return FALSE;
      }
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
