<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Plugin\media_library;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\media\MediaTypeInterface;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\media_library\Plugin\media_library\Type\TypeBase;

/**
 * Defines a media library type plugin for DAM assets.
 *
 * @MediaLibraryType(
 *   id = "wa_orange_dam",
 *   label = @Translation("Orange DAM"),
 *   description = @Translation("Select assets from Orange DAM."),
 * )
 */
final class DamMediaLibraryType extends TypeBase {

  /**
   * {@inheritdoc}
   */
  public function getAddForm(MediaTypeInterface $media_type): array {
    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['dam-media-library-add-form'],
      ],
    ];

    $form['container']['dam_form'] = [
      '#type' => 'inline_template',
      '#template' => '<iframe src="{{ url }}" width="100%" height="600" frameborder="0"></iframe>',
      '#context' => [
        'url' => Url::fromRoute('wa_orange_dam.ajax_media_form', [
          'media_type' => $media_type->id(),
        ])->toString(),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddFormTypeOptions(): array {
    $media_types = $this->entityTypeManager
      ->getStorage('media_type')
      ->loadByProperties(['source' => ['dam_image', 'dam_video', 'dam_file']]);

    $options = [];
    foreach ($media_types as $media_type) {
      $options[$media_type->id()] = $media_type->label();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context = []): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'create media');
  }

  /**
   * {@inheritdoc}
   */
  public function processAddForm(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    // Add JavaScript to handle the iframe communication
    $element['#attached']['library'][] = 'wa_orange_dam/ajax_content_browser';
    $element['#attached']['drupalSettings']['wa_orange_dam']['media_library'] = TRUE;
  }

}
