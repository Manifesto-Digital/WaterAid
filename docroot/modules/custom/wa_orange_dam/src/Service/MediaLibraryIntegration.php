<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Service for integrating DAM assets with Drupal's media library.
 */
final class MediaLibraryIntegration {

  use StringTranslationTrait;

  /**
   * Constructs a new MediaLibraryIntegration service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entity_type_manager,
    private readonly FormBuilderInterface $form_builder,
    private readonly RendererInterface $renderer,
    private readonly ModuleHandlerInterface $module_handler,
  ) {
  }

  /**
   * Provides DAM media types that can be used in media library.
   *
   * @return array
   *   Array of media type info.
   */
  public function getDamMediaTypes(): array {
    $types = [];

    $media_types = $this->entity_type_manager
      ->getStorage('media_type')
      ->loadMultiple();

    foreach ($media_types as $media_type) {
      $source_plugin = $media_type->getSource();

      // Check if this is a DAM media type
      if (in_array($source_plugin->getPluginId(), ['dam_image', 'dam_video', 'dam_file'])) {
        $types[$media_type->id()] = [
          'label' => $media_type->label(),
          'description' => $media_type->getDescription(),
          'source' => $source_plugin->getPluginId(),
        ];
      }
    }

    return $types;
  }

  /**
   * Gets the DAM form for use in media library modals.
   *
   * @param string $media_type_id
   *   The media type ID.
   *
   * @return array
   *   The form render array.
   */
  public function getDamForm(string $media_type_id): array {
    $form = $this->form_builder->getForm(
      'Drupal\wa_orange_dam\Form\AjaxMediaForm',
      $media_type_id
    );

    return $form;
  }

  /**
   * Creates a link to open the DAM browser in a modal.
   *
   * @param string $media_type_id
   *   The media type ID.
   * @param array $options
   *   Additional options for the link.
   *
   * @return array
   *   A renderable link array.
   */
  public function createDamBrowserLink(string $media_type_id, array $options = []): array {
    $url = Url::fromRoute('wa_orange_dam.ajax_media_form', [
      'media_type' => $media_type_id,
    ]);

    $default_options = [
      'attributes' => [
        'class' => ['use-ajax', 'dam-browser-link'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode([
          'width' => 800,
          'height' => 600,
          'title' => $this->t('Select DAM Asset'),
        ]),
      ],
    ];

    $options = array_merge_recursive($default_options, $options);
    $url->setOptions($options);

    return [
      '#type' => 'link',
      '#title' => $this->t('Browse DAM Assets'),
      '#url' => $url,
      '#attached' => [
        'library' => [
          'core/drupal.dialog.ajax',
          'wa_orange_dam/ajax_content_browser',
        ],
      ],
    ];
  }

  /**
   * Provides JavaScript settings for DAM integration.
   *
   * @param string $media_type_id
   *   The media type ID.
   *
   * @return array
   *   JavaScript settings array.
   */
  public function getJavaScriptSettings(string $media_type_id): array {
    $types = [];

    switch ($media_type_id) {
      case 'dam_image':
        $types = ['Images*'];
        break;
      case 'dam_video':
        $types = ['Videos*'];
        break;
      case 'dam_file':
        $types = [];
        break;
    }

    return [
      'wa_orange_dam' => [
        'types' => $types,
        'media_type' => $media_type_id,
        'media_library' => TRUE,
      ],
    ];
  }

  /**
   * Checks if media library module is available.
   *
   * @return bool
   *   TRUE if media library is available.
   */
  public function isMediaLibraryAvailable(): bool {
    return $this->module_handler->moduleExists('media_library');
  }

}
