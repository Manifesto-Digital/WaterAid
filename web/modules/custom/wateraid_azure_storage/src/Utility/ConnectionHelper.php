<?php

namespace Drupal\wateraid_azure_storage\Utility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Helper to connect with Azure.
 *
 * @package Drupal\wateraid_azure_storage\Utility
 */
abstract class ConnectionHelper {

  /**
   * Connection settings machine name.
   */
  public const FIELD = 'connection';

  /**
   * Builds the form.
   *
   * @param mixed[] $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed[] $config
   *   The config.
   */
  public static function buildForm(array &$form, FormStateInterface $form_state, array $config): void {

    $form[self::FIELD] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Connection settings'),
    ];

    // A default is provided to fallback to the "azure_storage.settings".
    $form[self::FIELD]['protocol'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Default Endpoints Protocol'),
      '#description' => new TranslatableMarkup('Default endpoints protocol to use.'),
      '#default_value' => $config['protocol'] ?? '',
      '#options' => [
        '' => new TranslatableMarkup('- Use default -'),
        'http' => new TranslatableMarkup('Http'),
        'https' => new TranslatableMarkup('Https'),
      ],
    ];

    $form[self::FIELD]['account_name'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Account Name'),
      '#description' => new TranslatableMarkup('The Account Name for Azure Storage'),
      '#default_value' => $config['account_name'] ?? NULL,
    ];

    // A default is provided to fallback to the "azure_storage.settings".
    $form[self::FIELD]['test_account_key'] = [
      '#type' => 'key_select',
      '#title' => new TranslatableMarkup('Account Key (test)'),
      '#description' => new TranslatableMarkup('The Account Key for Azure Storage'),
      '#default_value' => $config['test_account_key'] ?? '',
      '#empty_value' => '',
      '#empty_option' => new TranslatableMarkup('- Use default -'),
    ];

    // A default is provided to fallback to the "azure_storage.settings".
    $form[self::FIELD]['live_account_key'] = [
      '#type' => 'key_select',
      '#title' => new TranslatableMarkup('Account Key (live)'),
      '#description' => new TranslatableMarkup('The Account Key for Azure Storage'),
      '#default_value' => $config['live_account_key'] ?? '',
      '#empty_value' => '',
      '#empty_option' => new TranslatableMarkup('- Use default -'),
    ];

    $form[self::FIELD]['endpoint_suffix'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Endpoint Suffix'),
      '#description' => new TranslatableMarkup('The Endpoint Suffix for Azure Storage'),
      '#default_value' => $config['endpoint_suffix'] ?? NULL,
    ];

    $form[self::FIELD]['queue_name'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Queue Name'),
      '#description' => new TranslatableMarkup('The Azure Storage Queue Name. This field supports "prod", "live" and "test" queue mode identifiers.'),
      '#default_value' => $config['queue_name'] ?? NULL,
      '#required' => TRUE,
    ];

    $form[self::FIELD]['queue_mode_bypass'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Bypass Queue Mode Identifier'),
      '#description' => new TranslatableMarkup('Bypasses mode identifiers in queue names. This should not be used in Production!'),
      '#default_value' => $config['queue_mode_bypass'] ?? FALSE,
    ];
  }

}
