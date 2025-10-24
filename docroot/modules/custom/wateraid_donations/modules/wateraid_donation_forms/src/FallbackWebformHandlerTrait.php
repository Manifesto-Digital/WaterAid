<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FallbackTrait.
 *
 * @package Drupal\wateraid_donation_forms
 */
trait FallbackWebformHandlerTrait {

  /**
   * The Fallback Plugin manager.
   */
  protected ?FallbackPluginManager $fallbackPluginManager = NULL;

  /**
   * Gets the Fallback Plugin service.
   *
   * @return \Drupal\wateraid_donation_forms\FallbackPluginManager
   *   The Fallback plugin service.
   */
  public function getFallbackManager(): FallbackPluginManager {
    if (!$this->fallbackPluginManager) {
      $this->fallbackPluginManager = \Drupal::service('plugin.manager.fallback');
    }
    return $this->fallbackPluginManager;
  }

  /**
   * Helper to build a Fallback form.
   *
   * @param mixed[] $form
   *   The render form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state obj.
   * @param string $field_name
   *   The field the fallback applies to.
   *
   * @return mixed[]
   *   The render form.
   */
  public function buildFallbackForm(array $form, FormStateInterface $form_state, string $field_name): array {

    $options = [];
    foreach ($this->getFallbackManager()->getDefinitions() as $fallback_plugin_id => $definition) {
      /** @var \Drupal\wateraid_donation_forms\FallbackInterface $fallback_plugin */
      try {
        $fallback_plugin = $this->getFallbackManager()->createInstance($fallback_plugin_id);
        $options[$fallback_plugin_id] = $fallback_plugin->getLabel();
      }
      catch (PluginException $e) {
        // Something went wrong.
        \Drupal::logger('wateraid_donation_forms')
          ->error('Plugin error for @id: @error', [
            '@id' => $fallback_plugin_id,
            '@error' => $e->getMessage(),
          ]);
      }
    }

    $form['fallback_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fallback settings'),
      '#description' => $this->t('Sets the fallback settings'),
    ];

    $form['fallback_settings']['fallback_plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Fallback method'),
      '#options' => $options,
      '#empty_option' => $this->t('- None -'),
      '#description' => $this->t('The fallback method to be applied on "@field_name"', [
        '@field_name' => $field_name,
      ]),
      '#default_value' => $this->configuration['fallback_plugin'] ?? NULL,
      '#ajax' => [
        'callback' => [$this, 'fallbackPluginAjaxCallback'],
        'wrapper' => 'fallback-plugin-ajax-container',
      ],
    ];

    $form['fallback_settings']['fallback_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'fallback-plugin-ajax-container',
      ],
    ];

    // Attempt to extract from $form_state or config.
    $active_fallback_plugin_id = $form_state->getValue(['fallback_settings', 'fallback_plugin'])
      ?: $this->configuration['fallback_plugin'];

    if ($active_fallback_plugin_id) {
      /** @var \Drupal\wateraid_donation_forms\FallbackInterface $active_fallback_plugin */
      try {
        $active_fallback_plugin = $this->getFallbackManager()->createInstance($active_fallback_plugin_id);

        $form['fallback_settings']['fallback_wrapper']['fallback_message'] = [
          '#type' => 'webform_html_editor',
          '#title' => $this->t('Fallback on @field_name', [
            '@field_name' => $field_name,
          ]),
          '#description' => $this->t('This message will be used as fallback on "@field_name"', [
            '@field_name' => $field_name,
          ]),
          '#default_value' => $this->configuration['fallback_message'] ?? NULL,
        ];

        $form['fallback_settings']['fallback_wrapper']['fallback_description'] = [
          '#type' => 'markup',
          '#markup' => $this->t('<strong>Note:</strong> @description', [
            '@description' => $active_fallback_plugin->getDescription(),
          ]),
        ];
      }
      catch (PluginException $e) {
        // Something went wrong.
        \Drupal::logger('wateraid_donation_forms')
          ->error('Plugin error for @id: @error', [
            '@id' => $active_fallback_plugin_id,
            '@error' => $e->getMessage(),
          ]);
      }
    }

    return $form;
  }

  /**
   * AJAX callback for fallback plugin.
   *
   * @param mixed[] $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form State.
   *
   * @return mixed[]
   *   The section of the form that is AJAXified.
   */
  public function fallbackPluginAjaxCallback(array &$form, FormStateInterface $form_state): array {
    return $form['settings']['fallback_settings']['fallback_wrapper'];
  }

}
