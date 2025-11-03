<?php

namespace Drupal\wa_orange_dam\Form;

/**
 * Provides an AJAX form for creating DAM video entities in modal dialogs.
 */
final class AjaxVideoForm extends AjaxMediaForm {

  /**
   * {@inheritdoc}
   */
  protected function getDefaultMediaType(): string {
    return 'dam_video';
  }

}
