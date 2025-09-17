<?php

/**
 * @file
 * Deploy hooks for the Wateraid Forms module.
 */

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Add the url_params field.
 */
function wateraid_forms_deploy_install_url_params(): void {
  $url_params_field_definition = BaseFieldDefinition::create('map')
    ->setName('url_params')
    ->setLabel(t('Url parameters'));

  \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition('url_params', 'webform_submission', 'wateraid_forms', $url_params_field_definition);
}
