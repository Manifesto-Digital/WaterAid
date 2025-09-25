<?php

namespace Drupal\just_giving\Plugin\Block;

use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Provides a 'JustGivingUserForm' block.
 *
 * @Block(
 *  id = "just_giving_user_form",
 *  admin_label = @Translation("Just giving user form"),
 * )
 */
class JustGivingUserForm extends BlockBase {

  /**
   * The form builder.
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * Constructs a Block object.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The current_user.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected FormBuilderInterface $form_builder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->formBuilder = $form_builder;
  }

  /**
   * Creates the block.
   *
   * @param \Drupal\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param mixed[] $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, string $plugin_id, mixed $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return $this->formBuilder->getForm('Drupal\just_giving\Form\JustGivingUserForm');
  }

}
