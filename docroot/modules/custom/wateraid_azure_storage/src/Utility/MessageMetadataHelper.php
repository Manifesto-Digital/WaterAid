<?php

namespace Drupal\wateraid_azure_storage\Utility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Helper for managing message metadata.
 *
 * @package Drupal\wateraid_azure_storage\Utility
 */
abstract class MessageMetadataHelper {

  /**
   * Message machine name.
   */
  public const FIELD = 'message_metadata';

  /**
   * Builds the form.
   *
   * @param mixed[] $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed[] $config
   *   The config.
   */
  public static function buildForm(array &$form, FormStateInterface $form_state, array $config): void {

    $form['#tree'] = TRUE;

    $wrapper_id = 'metadata-fieldset-wrapper';

    $form[self::FIELD] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Extra metadata'),
      '#description' => new TranslatableMarkup('Set additional metadata values that are to be sent across to the Azure Storage Queue.'),
      '#weight' => -5,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    $form[self::FIELD]['map'] = [
      '#type' => 'table',
      '#header' => [
        new TranslatableMarkup('Key'),
        new TranslatableMarkup('Value'),
      ],
    ];

    for ($i = 0; $i < 5; $i++) {
      $form[self::FIELD]['map'][$i] = [
        'key' => [
          '#type' => 'textfield',
          '#default_value' => $config[self::FIELD][$i]['key'] ?? NULL,
        ],
        'value' => [
          '#type' => 'textfield',
          '#default_value' => $config[self::FIELD][$i]['value'] ?? NULL,
        ],
      ];
    }

    $form[self::FIELD]['actions'] = [
      '#type' => 'actions',
    ];
  }

}
