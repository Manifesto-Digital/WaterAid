<?php

namespace Drupal\wateraid_base_core\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Donation Page' condition.
 *
 * This has to be in wateraid_base_core (and not a donation-specific module),
 * because there are global blocks that use this condition (to be set to hidden
 * on donation pages).
 *
 * @Condition(
 *   id = "donation_page",
 *   label = @Translation("Donation Page"),
 *   context_definitions = {
 *     "webform" = @ContextDefinition("entity:webform", label = @Translation("Webform"), required = FALSE),
 *   }
 * )
 */
class DonationPage extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Donation page constructor.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, string $plugin_id, mixed $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
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
  public function evaluate(): bool {
    if (empty($this->configuration['donation_page']) && !$this->isNegated()) {
      return TRUE;
    }

    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      // For revision pages, $node is the node ID.
      // If $node is not an instance of Node, explicitly fetch the object.
      if (!($node && $node instanceof NodeInterface)) {
        $node = $this->entityTypeManager->getStorage('node')->load($node);
      }
      if ($node->bundle() === 'donation_landing_page') {
        return TRUE;
      }
      elseif ($node->bundle() == 'webform' && $node->hasField('field_c_w_donation_menu') && $node->get('field_c_w_donation_menu')->value === '1') {
        // Show on Webform nodes where the option is enabled.
        return TRUE;
      }
    }

    if ($webform = $this->getContextValue('webform')) {
      $handlers = $webform->getHandlers('wateraid_donations');
      return count($handlers) > 0;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // @todo Implement summary() method.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    if (!empty($this->configuration['donation_page'])) {
      $this->configuration['donation_page'] = $form_state->getValue('donation_page');
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['donation_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show this block on donation pages.'),
      '#default_value' => $this->configuration['donation_page'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return ['donation_page' => FALSE] + parent::defaultConfiguration();
  }

}
