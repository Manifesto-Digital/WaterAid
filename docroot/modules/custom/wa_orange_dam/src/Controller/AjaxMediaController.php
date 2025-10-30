<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\media\MediaInterface;
use Drupal\wa_orange_dam\Service\Api;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for AJAX operations related to DAM media entities.
 */
final class AjaxMediaController extends ControllerBase {

  /**
   * Constructs a new AjaxMediaController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\wa_orange_dam\Service\Api $wa_orange_dam_api
   *   The Orange DAM API service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entity_type_manager,
    private readonly EntityFormBuilderInterface $entity_form_builder,
    private readonly FormBuilderInterface $form_builder,
    private readonly Api $wa_orange_dam_api,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('form_builder'),
      $container->get('wa_orange_dam.api'),
    );
  }

  /**
   * Returns the DAM media selection form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $media_type
   *   The media type (dam_image, dam_video, etc.).
   *
   * @return array
   *   The form render array.
   */
  public function mediaSelectionForm(Request $request, string $media_type = 'dam_image'): array {
    $form = $this->form_builder->getForm(
      'Drupal\wa_orange_dam\Form\AjaxMediaForm',
      $media_type
    );

    return $form;
  }

  /**
   * Creates a media entity from DAM asset data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function createMediaEntity(Request $request): AjaxResponse {
    $response = new AjaxResponse();

    $system_identifier = $request->request->get('system_identifier');
    $media_type_id = $request->request->get('media_type', 'dam_image');
    $opener_id = $request->request->get('opener_id');

    if (empty($system_identifier)) {
      $response->addCommand(new InvokeCommand(NULL, 'showMessage', [
        $this->t('Please select a DAM asset.'),
        'error'
      ]));
      return $response;
    }

    // Validate the DAM asset exists
    if ($api_result = $this->wa_orange_dam_api->search([
      'query' => 'SystemIdentifier:' . $system_identifier,
    ])) {
      if (!empty($api_result['APIResponse']['Items'][0])) {
        // Create the media entity
        $media = $this->createMedia($media_type_id, $system_identifier, $api_result['APIResponse']['Items'][0]);

        if ($media) {
          $media->save();

          // Return success response with media data
          $response->addCommand(new InvokeCommand(NULL, 'damMediaSelected', [
            [
              'id' => $media->id(),
              'uuid' => $media->uuid(),
              'name' => $media->label(),
              'type' => $media_type_id,
              'opener_id' => $opener_id
            ]
          ]));

          // Close the modal
          $response->addCommand(new CloseModalDialogCommand());
        }
        else {
          $response->addCommand(new InvokeCommand(NULL, 'showMessage', [
            $this->t('Failed to create media entity.'),
            'error'
          ]));
        }
      }
      else {
        $response->addCommand(new InvokeCommand(NULL, 'showMessage', [
          $this->t('DAM asset not found.'),
          'error'
        ]));
      }
    }
    else {
      $response->addCommand(new InvokeCommand(NULL, 'showMessage', [
        $this->t('Error communicating with DAM service.'),
        'error'
      ]));
    }

    return $response;
  }

  /**
   * Validates a DAM asset identifier via AJAX.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function validateDamAsset(Request $request): JsonResponse {
    $system_identifier = $request->request->get('system_identifier');

    if (empty($system_identifier)) {
      return new JsonResponse(['valid' => FALSE, 'message' => 'No identifier provided']);
    }

    $valid = FALSE;
    $message = '';
    $asset_data = NULL;

    if ($api_result = $this->wa_orange_dam_api->search([
      'query' => 'SystemIdentifier:' . $system_identifier,
    ])) {
      if (!empty($api_result['APIResponse']['Items'][0])) {
        $valid = TRUE;
        $asset_data = $api_result['APIResponse']['Items'][0];
        $message = 'Asset found';
      }
      else {
        $message = 'This ID does not exist on the Orange DAM.';
      }
    }
    else {
      $message = 'Error communicating with DAM service.';
    }

    return new JsonResponse([
      'valid' => $valid,
      'message' => $message,
      'asset_data' => $asset_data,
    ]);
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
