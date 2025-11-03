<?php

namespace Drupal\wa_orange_dam\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an AJAX form for creating DAM file entities in modal dialogs.
 */
final class AjaxFileForm extends AjaxMediaForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $media_type = 'dam_file'): array {
    return parent::buildForm($form, $form_state, $media_type);
  }
}
