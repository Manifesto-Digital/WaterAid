<?php

namespace Drupal\wateraid_forms\Plugin\Styleguide;

use Drupal\Core\Form\FormBuilder;
use Drupal\styleguide\Generator;
use Drupal\styleguide\GeneratorInterface;
use Drupal\styleguide\Plugin\StyleguidePluginBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * WaterAid "Page Controllers" Styleguide items implementation.
 *
 * @Plugin(
 *   id = "wateraid_forms_styleguide",
 *   label = @Translation("Wateraid Forms Styleguide elements")
 * )
 */
class WebformsStyleguide extends StyleguidePluginBase {
  /**
   * The form builder.
   */
  protected FormBuilder $formBuilder;

  /**
   * The request stack.
   */
  protected RequestStack $requestStack;

  /**
   * The styleguide generator service.
   */
  protected Generator $generator;

  /**
   * The webform element plugin manager.
   */
  protected WebformElementManagerInterface $elementManager;

  /**
   * Constructs a new InterruptorStyleguide.
   *
   * @param mixed[] $configuration
   *   The configuraiton.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Drupal\styleguide\GeneratorInterface $styleguide_generator
   *   The style generator object.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   A webform element manager object.
   */
  public function __construct(array $configuration, string $plugin_id, mixed $plugin_definition, FormBuilder $form_builder, RequestStack $request_stack, GeneratorInterface $styleguide_generator, WebformElementManagerInterface $element_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->requestStack = $request_stack;
    $this->generator = $styleguide_generator;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('request_stack'),
      $container->get('styleguide.generator'),
      $container->get('plugin.manager.webform.element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function items() {
    $items = [];

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('wa_test_webform');
    $form = $webform->getSubmissionForm();

    $items['webform'] = [
      'title' => $this->t('Webforms, basic'),
      'content' => $form,
      'group' => $this->t('Webform'),
    ];

    return $items;
  }

}
