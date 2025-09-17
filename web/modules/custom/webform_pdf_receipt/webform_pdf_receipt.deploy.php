<?php

/**
 * @file
 * Deploy hooks for the Webform PDF Receipt module.
 */

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Add the file ID field.
 */
function webform_pdf_receipt_deploy_install_file_id(): void {
  $file_id = BaseFieldDefinition::create('integer')
    ->setName('file_id')
    ->setLabel(t('File ID'))
    ->setDescription(t('Stores the generated pdf file id.'))
    ->setTargetEntityTypeId('webform_submission');

  \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition('file_id', 'webform_submission', 'webform_pdf_receipt', $file_id);
}
