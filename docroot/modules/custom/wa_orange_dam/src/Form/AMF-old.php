<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\wa_orange_dam\Service\Api;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an AJAX form for creating DAM media entities in modal dialogs.
 */
final class AjaxMediaForm extends FormBase {

  /**
   * Constructs a new AjaxMediaForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\wa_orange_dam\Service\Api $wa_orange_dam_api
   *   The Orange DAM API service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entity_type_manager,
    private readonly Api $wa_orange_dam_api,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('wa_orange_dam.api'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wa_orange_dam_ajax_media_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $media_type = 'dam_image'): array {
    // Store the media type for later use
    $form_state->set('media_type_id', $media_type);

    // Load the media type to get the types for the DAM browser
    $types = $this->getDamTypes($media_type);

    $form['#prefix'] = '<div id="dam-media-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['system_identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DAM Asset ID'),
      '#description' => $this->t('Enter the Orange DAM asset identifier or use the button below to browse.'),
      '#default_value' => '',
      '#attributes' => [
        'id' => 'orange-dam-identifier',
      ],
    ];

    $form['dam_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Browse DAM Assets'),
      '#attributes' => [
        'id' => 'orange-dam-open',
        'class' => ['button', 'button--primary'],
      ],
      '#attached' => [
        'library' => [
          'wa_orange_dam/ajax_content_browser',
        ],
        'drupalSettings' => [
          'wa_orange_dam' => [
            'types' => $types,
            'form_wrapper' => 'dam-media-form-wrapper',
          ],
        ],
      ],
    ];

    // Add validation messages area
    $form['messages'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'dam-messages',
        'class' => ['dam-messages'],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save_and_select'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add to Library'),
      '#ajax' => [
        'callback' => '::submitFormCallback',
        'wrapper' => 'dam-media-form-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Creating media...'),
        ],
      ],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#ajax' => [
        'callback' => '::cancelCallback',
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback for form submission.
   */
  public function submitFormCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    $valid = FALSE;
    $systemIdentifier = $form_state->getValue('system_identifier');
    $mediaTypeId = $form_state->get('media_type_id');
    $damItemData = [];


    if ($systemIdentifier) {
      if ($apiResult = $this->wa_orange_dam_api->search([
        'query' => 'SystemIdentifier:' . $systemIdentifier,
      ])) {
        if (!empty($apiResult['APIResponse']['Items'][0])) {

          // The id works, so this is now valid.
          $valid = TRUE;

          // Add the width and height properties.
          if (!empty($apiResult['APIResponse']['Items'][0])) {
            $apiResponseItem = $apiResult['APIResponse']['Items'][0];

            // The id works, so this is now valid.
            $valid = TRUE;

            // Add the DAM properties to the Media.
            if (isset($apiResponseItem['path_TR1'])) {
              if (isset($apiResponseItem['path_TR1']['Width'])) {
                $value[0]['width'] = $apiResponseItem['path_TR1']['Width'];
              }
              if (isset($apiResponseItem['path_TR1']['Height'])) {
                $value[0]['height'] = $apiResponseItem['path_TR1']['Height'];
              }
            }
          }

        }
      }

    }

    // die(var_dump(
    //   empty($system_identifier),
    //   $dam_item_data
    // ));

    if (empty($systemIdentifier) || !$valid) {
      $response->addCommand(new ReplaceCommand('#dam-messages',
        '<div id="dam-messages" class="dam-messages">' .
        '<div class="messages messages--error">' .
        $this->t('Please select a valid DAM asset.') .
        '</div></div>'
      ));
      return $response;
    }

    // Create the media entity
    $media = $this->createMedia('dam_image', $systemIdentifier, $dam_item_data);

    if ($media) {
      $media->save();

      // Trigger JavaScript event to notify the media library
      $response->addCommand(new InvokeCommand(NULL, 'damMediaCreated', [
        [
          'id' => $media->id(),
          'uuid' => $media->uuid(),
          'name' => $media->label(),
          'type' => $media_type_id,
        ]
      ]));

      // Close the modal
      $response->addCommand(new CloseModalDialogCommand());

      // Show success message
      $this->messenger()->addMessage($this->t('Media "@title" has been created.', ['@title' => $media->label()]));
    } else {
      $response->addCommand(new ReplaceCommand('#dam-messages',
        '<div id="dam-messages" class="dam-messages">' .
        '<div class="messages messages--error">' .
        $this->t('Failed to create media entity.') .
        '</div></div>'
      ));
    }

    return $response;
  }

  /**
   * AJAX callback for cancel button.
   */
  public function cancelCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // This method is required by FormInterface but not used in AJAX context
  }

  /**
   * Gets the DAM types for the given media type.
   *
   * @param string $media_type_id
   *   The media type ID.
   *
   * @return array
   *   Array of DAM types.
   */
  private function getDamTypes(string $media_type_id): array {
    switch ($media_type_id) {
      case 'dam_image':
        return ['Images*'];

      case 'dam_video':
        return ['Videos*'];

      case 'dam_file':
        return [];

      default:
        return [];
    }
  }

  /**
   * Builds HTML preview for a DAM asset.
   *
   * @param array $asset_data
   *   The asset data from the DAM API.
   *
   * @return string
   *   The HTML preview.
   */
  private function buildAssetPreview(array $asset_data): string {
    $title = $asset_data['CoreField']['Title'] ?? 'Untitled Asset';
    $caption = $asset_data['CaptionShort'] ?? '';

    $preview = '<div class="dam-asset-info">';
    $preview .= '<h4>' . htmlspecialchars($title) . '</h4>';

    if ($caption) {
      $preview .= '<p>' . htmlspecialchars($caption) . '</p>';
    }

    // Add dimension info if available
    if (isset($asset_data['path_TR1']['Width']) && isset($asset_data['path_TR1']['Height'])) {
      $preview .= '<p><strong>Dimensions:</strong> ' .
        $asset_data['path_TR1']['Width'] . ' x ' .
        $asset_data['path_TR1']['Height'] . '</p>';
    }

    $preview .= '</div>';

    return $preview;
  }

  /**
   * Creates a media entity from DAM data.
   *
   * @param string $media_type_id
   *   The media type ID.
   * @param string $system_identifier
   *   The DAM system identifier.
   * @param array $dam_item_data
   *   Additional DAM item data from the API.
   *
   * @return \Drupal\media\MediaInterface|null
   *   The created media entity or NULL on failure.
   */
  private function createMedia(string $media_type_id, string $system_identifier, array $dam_item_data): ?MediaInterface {
    try {
      $media_type = $this->entity_type_manager->getStorage('media_type')->load($media_type_id);

      if (!$media_type) {
        return NULL;
      }

      $source_field = $media_type->getSource()->getSourceFieldDefinition($media_type);

      // Prepare field data - same structure as DamWidget
      $field_data = [
        'system_identifier' => $system_identifier,
      ];

      // Add width and height if available (same logic as DamWidget)
      foreach (['Width', 'Height'] as $key) {
        if (isset($dam_item_data['path_TR1'][$key])) {
          $field_data[strtolower($key)] = $dam_item_data['path_TR1'][$key];
        }
      }

      // Use the title from DAM if available, otherwise use the system identifier
      $title = $dam_item_data['CoreField']['Title'] ?? 'DAM Asset ' . $system_identifier;

      $media = $this->entity_type_manager->getStorage('media')->create([
        'bundle' => $media_type_id,
        'name' => $title,
        $source_field->getName() => $field_data,
      ]);

      return $media;
    }
    catch (\Exception $e) {
      \Drupal::logger('wa_orange_dam')->error('Failed to create media entity: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

}
