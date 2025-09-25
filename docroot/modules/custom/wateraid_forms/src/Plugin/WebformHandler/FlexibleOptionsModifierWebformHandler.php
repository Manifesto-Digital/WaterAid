<?php

namespace Drupal\wateraid_forms\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;

/**
 * Handler for the Confirmation Page.
 *
 * @package Drupal\wateraid_donation_forms\Plugin\WebformHandler
 *
 * @WebformHandler(
 *   id = "flexible_options_modifier",
 *   label = @Translation("Flexible options modifier"),
 *   category = @Translation("Other"),
 *   description = @Translation("Modifies flexible options."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class FlexibleOptionsModifierWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['options_modifier'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Option modification settings'),
    ];

    $max = 6;
    for ($i = 1; $i <= $max; $i++) {
      $form['options_modifier'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Condition @number', ['@number' => $i]),
      ];
      $form['options_modifier'][$i]['source_field'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Source field'),
        '#default_value' => $this->configuration['options_modifier'][$i]['source_field'] ?? '',
        '#description' => $this->t('ID of the Webform field to read.'),
      ];
      $form['options_modifier'][$i]['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Value'),
        '#default_value' => $this->configuration['options_modifier'][$i]['value'] ?? '',
        '#description' => $this->t('The value within the source field which will trigger the corresponding action.'),
      ];
      $form['options_modifier'][$i]['action'] = [
        '#type' => 'select',
        '#title' => $this->t('Action'),
        '#options' => [
          'remove' => $this->t('Hide'),
        ],
        '#empty_option' => $this->t('None'),
        '#default_value' => $this->configuration['options_modifier'][$i]['action'] ?? '',
        '#description' => $this->t('Action to take when the condition is met.'),
      ];
      $form['options_modifier'][$i]['option'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Option'),
        '#default_value' => $this->configuration['options_modifier'][$i]['option'] ?? '',
        '#description' => $this->t('ID of the option to perform the action on.'),
      ];
    }

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['options_modifier'] = $form_state->getValue('options_modifier');
  }

  /**
   * {@inheritdoc}
   */
  public function alterElement(array &$element, FormStateInterface $form_state, array $context) {
    if (isset($element['#type']) && $element['#type'] == 'flexible_options') {
      if (!empty($this->configuration['options_modifier'])) {
        foreach ($this->configuration['options_modifier'] as $modifier) {
          $source_field = $modifier['source_field'] ?? '';
          $value = $modifier['value'] ?? '';
          $action = $modifier['action'] ?? '';
          $option = $modifier['option'] ?? '';
          if (!empty($source_field) && $action == 'remove' && $form_state->hasValue($source_field) && $form_state->getValue($source_field) == $value && isset($element['#options'][$option])) {
            unset($element['#options'][$option]);
          }
        }
      }
    }
  }

}
