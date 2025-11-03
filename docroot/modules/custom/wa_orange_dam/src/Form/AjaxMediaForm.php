<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wa_orange_dam\Service\Api;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an AJAX form for creating DAM media entities in modal dialogs.
 */
class AjaxMediaForm extends FormBase {

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
   * Gets the default media type for the form.
   *
   * @return string
   *   The media type ID.
   */
  protected function getDefaultMediaType(): string {
    return 'dam_image';
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
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Load the media type to get the types for the DAM browser.
    $types = $this->getDamTypes();

    $form['#prefix'] = '<div id="dam-media-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['system_identifier'] = [
      '#type' => 'hidden',
      '#default_value' => '',
      '#attributes' => [
        'id' => 'orange-dam-identifier',
      ],
    ];

    $form['messages'] = [
      '#type' => 'markup',
      '#markup' => '<div id="dam-messages"></div>',
      '#weight' => -10,
    ];

    $form['dam_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Browse DAM Assets'),
      '#attributes' => [
        'id' => 'orange-dam-open',
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // This method is required by FormInterface but not used in AJAX context.
  }

  /**
   * Gets the DAM types for the given media type.
   *
   * @return array
   *   Array of DAM types.
   */
  private function getDamTypes(): array {
    switch ($this->getDefaultMediaType()) {
      case 'dam_image':
        return ['Images*'];

      case 'dam_video':
        return ['Videos*'];

      case 'dam_file':
        return ['Audio*', 'Others*'];

      default:
        return [];
    }
  }

}
