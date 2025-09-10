<?php

namespace Drupal\wateraid_azure_storage\Utility;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\WebformInterface;

/**
 * Helper for cloning data..
 *
 * @package Drupal\wateraid_azure_storage\Utility
 */
abstract class CloneHandlerDataHelper {

  /**
   * Helper class index key name.
   */
  public const FIELD = 'clone_handler_data';

  /**
   * Builds the form.
   *
   * @param mixed[] $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed[] $config
   *   The config array.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   */
  public static function buildForm(array &$form, FormStateInterface $form_state, array $config, WebformInterface $webform): void {

    // Capture in session for ::ajaxCallback.
    $form_state->set('webform', $webform);

    $form[self::FIELD] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Copy handler data'),
    ];

    $webform_ids = \Drupal::service('entity_type.manager')
      ->getStorage('webform')
      ->getQuery()
      ->accessCheck(FALSE)
      ->execute();

    $form[self::FIELD]['source_webform'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Source Webform'),
      '#options' => [
        '' => new TranslatableMarkup('- None -'),
      ] + $webform_ids ?: [],
      '#description' => new TranslatableMarkup('The source Webform to copy the configuration from.'),
      '#default_value' => '',
    ];

    $warnings = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#title' => 'Warning',
      '#items' => [
        new TranslatableMarkup('This will overwrite any existing data in the "Submission data" and "Extra metadata" mapping above.'),
        new TranslatableMarkup('You need to have saved this handler at least once before using this feature.'),
      ],
    ];

    $form[self::FIELD]['actions']['copy_handler_data'] = [
      '#type' => 'submit',
      '#prefix' => \Drupal::service('renderer')->render($warnings),
      '#value' => new TranslatableMarkup('Copy handler data'),
      '#attributes' => [
        'class' => ['button--primary'],
      ],
      '#ajax' => [
        'callback' => [self::class, 'copyHandlerDataAjaxCallback'],
        'event' => 'click',
      ],
    ];
  }

  /**
   * Callback for the clone func.
   *
   * @param mixed[] $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public static function copyHandlerDataAjaxCallback(array &$form, FormStateInterface $form_state): AjaxResponse {

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->get('webform') ?: NULL;
    $webform_azure_storage_queue_handler_id = 'wateraid_azure_storage_queue';

    if ($webform === NULL) {
      throw new \Exception('Webform not defined.');
    }

    $source_webform_id = $form_state->getValue([
      'settings', self::FIELD, 'source_webform',
    ]) ?: NULL;

    if (empty($source_webform_id)) {
      \Drupal::messenger()->addWarning(new TranslatableMarkup('No source Webform was selected.'));
    }
    else {
      /** @var \Drupal\Core\Entity\EntityTypeManager $entity_type_manager */
      $entity_type_manager = \Drupal::service('entity_type.manager');
      /** @var \Drupal\webform\WebformInterface $source_webform */
      $source_webform = $entity_type_manager->getStorage('webform')->load($source_webform_id);

      if ($source_webform === NULL) {
        \Drupal::messenger()->addWarning(new TranslatableMarkup('Webform was not found.'));
      }
      else {
        $source_handlers = $source_webform->getHandlers($webform_azure_storage_queue_handler_id);

        if ($source_handlers->count() === 0) {
          \Drupal::messenger()->addWarning('Source Webform has no Azure Storage Queue handler.');
        }
        else {
          /** @var \Drupal\webform\Plugin\WebformHandlerInterface $source_handler */
          $source_handler = $source_handlers->getIterator()->current();
          $source_config = $source_handler->getConfiguration();

          // Extract the map settings from source.
          $source_submission_data_settings = $source_config['settings'][MessageSubmissionDataHelper::FIELD] ?? NULL;
          $source_metadata_settings = $source_config['settings'][MessageMetadataHelper::FIELD] ?? NULL;

          if ($source_submission_data_settings === NULL && $source_metadata_settings === NULL) {
            \Drupal::messenger()->addWarning('Source Webform has no Azure Storage Queue handler mapping specified.');
          }
          else {
            // Copy over to destination handler.
            $handlers = $webform->getHandlers($webform_azure_storage_queue_handler_id);
            if ($handlers->count() === 0) {
              \Drupal::messenger()->addWarning('Destination Webform has no Azure Storage Queue handler.');
            }
            else {
              // Update destination via config factory as there doesn't appear
              // to be a way to do this via either a Webform or Handler object.
              $config = \Drupal::configFactory()->getEditable('webform.webform.' . $webform->id());
              $config_key_submission_data = 'handlers.' . $webform_azure_storage_queue_handler_id . '.settings.' . MessageSubmissionDataHelper::FIELD;
              $config_key_metadata = 'handlers.' . $webform_azure_storage_queue_handler_id . '.settings.' . MessageMetadataHelper::FIELD;

              // Save the new config on the destination handler.
              $config
                ->set($config_key_submission_data, $source_submission_data_settings)
                ->set($config_key_metadata, $source_metadata_settings)
                ->save();

              \Drupal::messenger()->addStatus(new TranslatableMarkup('The webform handler configuration was successfully cloned.'));
            }
          }
        }
      }
    }

    $url = $webform->toUrl('handlers', ['query' => ['update' => $webform_azure_storage_queue_handler_id]]);
    $command = new RedirectCommand($url->toString());

    return (new AjaxResponse())->addCommand($command);
  }

}
