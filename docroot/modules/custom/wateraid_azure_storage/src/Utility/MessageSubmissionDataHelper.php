<?php

namespace Drupal\wateraid_azure_storage\Utility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\WebformInterface;

/**
 * Submission Data elper.
 *
 * @package Drupal\wateraid_azure_storage\Utility
 */
abstract class MessageSubmissionDataHelper {

  /**
   * Message machine name.
   */
  public const FIELD = 'message_submission_data';

  /**
   * Builds the form.
   *
   * @param mixed[] $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed[] $config
   *   The config.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   */
  public static function buildForm(array &$form, FormStateInterface $form_state, array $config, WebformInterface $webform): void {

    $form[self::FIELD] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Submission data'),
      '#weight' => -10,
    ];

    $form[self::FIELD]['included_columns'] = [
      '#type' => 'wateraid_webform_columns',
      '#title' => new TranslatableMarkup('Posted data'),
      '#title_display' => 'invisible',
      '#webform_id' => $webform->id(),
      '#required' => TRUE,
      '#default_value' => $config[self::FIELD] ?? [],
    ];

  }

}
