<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\media\MediaInterface;
use Drupal\wa_orange_dam\Service\Api;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel for the module.
   * @param \Drupal\wa_orange_dam\Service\Api $wa_orange_dam_api
   *   The Orange DAM API service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entity_type_manager,
    private readonly EntityFormBuilderInterface $entity_form_builder,
    private readonly FormBuilderInterface $form_builder,
    private readonly LoggerInterface $logger,
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
      $container->get('logger.factory')->get('wa_orange_dam'),
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
    $valid = FALSE;
    $systemIdentifier = $request->request->get('asset_id');
    $drupalMediaType = $request->request->get('drupal_media_type');

    $damItemData = [];

    if ($systemIdentifier) {
      if ($apiResult = $this->wa_orange_dam_api->search([
        'query' => 'SystemIdentifier:' . $systemIdentifier,
      ])) {
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

    if (empty($systemIdentifier) || !$valid) {
      $response->addCommand(new ReplaceCommand('#dam-messages',
        '<div id="dam-messages" class="dam-messages">' .
        '<div class="messages messages--error">' .
        $this->t('Please select a valid DAM asset.') .
        '</div></div>'
      ));
      return $response;
    }

    // Create the media entity.
    $media = $this->createMedia($drupalMediaType, $systemIdentifier, $damItemData);

    if (in_array($drupalMediaType, ['dam_image', 'dam_video'])) {

      // Set alt text if available.
      if (isset($apiResponseItem['CoreField']['Title'])) {
        $media->set('field_media_image_alt', $apiResponseItem['CoreField']['Title']);
      }
      if (isset($apiResponseItem['CustomField.Caption'])) {

        // Only set the caption if it doesn't contain non-ASCII characters.
        if (!preg_match('/[^\x20-\x7e]/', $apiResponseItem['CustomField.Caption'])) {
          $media->set('field_caption', substr(strip_tags($apiResponseItem['CustomField.Caption']), 0, 250));
        }
      }
      if (isset($apiResponseItem['customfield.Credit']['Value'])) {
        $media->set('field_credit', $apiResponseItem['customfield.Credit']['Value']);
      }
    }

    if ($media) {
      $media->save();

      // Trigger modal reload.
      $response->addCommand(new InvokeCommand('a[data-title*="DAM"].active', 'click'));
      $response->addCommand(new InvokeCommand('.media-library-widget-modal input[type="submit"][value="Apply filters"]', 'click'));
    }
    else {
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

      // Prepare field data - same structure as DamWidget.
      $field_data = [
        'system_identifier' => $system_identifier,
      ];

      // Add width and height if available (same logic as DamWidget).
      foreach (['Width', 'Height'] as $key) {
        if (isset($dam_item_data['path_TR1'][$key])) {
          $field_data[strtolower($key)] = $dam_item_data['path_TR1'][$key];
        }
      }

      // Use the title from DAM title, system identifier otherwise.
      $title = $dam_item_data['CoreField']['Title'] ?? 'DAM Asset ' . $system_identifier;

      $media = $this->entity_type_manager->getStorage('media')->create([
        'bundle' => $media_type_id,
        'name' => $title,
        $source_field->getName() => $field_data,
      ]);

      return $media;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to create media entity: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

}
