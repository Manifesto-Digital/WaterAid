<?php

/**
 * @file
 * Deploy hooks for the Group Webform module.
 */

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;

/**
 * Create the media icons.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function wa_editor_experience_deploy_generate_icons(): void {
  $media_storage = \Drupal::entityTypeManager()->getStorage('media');
  $file_storage = \Drupal::entityTypeManager()->getStorage('file');

  $path = \Drupal::service('extension.list.module')->getPath('wa_editor_experience');
  $dir = new DirectoryIterator($path . '/icons');

  /** @var \Drupal\file\FileRepositoryInterface $file_repository */
  $file_repository = \Drupal::service('file.repository');

  /** @var \Drupal\Core\File\FileSystemInterface $file_system */
  $file_system = \Drupal::service('file_system');
  $directory = 'public://icons/';
  $file_system->prepareDirectory($directory, FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

  foreach ($dir as $file_info) {
    if (!$file_info->isDot()) {

      // Get the file name.
      $source_name = $file_info->getFilename();
      $title = substr($source_name, 5);
      $file_name = strtolower(str_replace(' ', '_', $title));
      $title = substr($title, 0, -4);

      if ($media_storage->loadByProperties([
        'name' => $title,
      ])) {

        // We've already created this one: nothing to do.
        continue;
      }

      $image_data = file_get_contents($path . '/icons/' . $source_name);
      $image = $file_repository->writeData($image_data, $directory . $file_name, FileExists::Replace);

      try {

        // Create the SVG file.
        $file = $file_storage->create([
          'filename' => $file_name,
          'uri' => $directory . $file_name,
          'status' => 1,
          'uid' => 1,
        ]);
        $file->save();

        // Create the media item.
        $image_media = $media_storage->create([
          'name' => $title,
          'bundle' => 'icon_library',
          'uid' => 1,
          'status' => 1,
          'field_media_svg' => [
            'target_id' => $image->id(),
            'alt' => 'Icon of a ' . $title,
            'title' => $title,
          ],
        ]);
        $image_media->save();
      }
      catch (Exception $e) {
        \Drupal::logger('wa_editor_experience')->error(t('Error trying to create icon media. :error', [
          ':error' => $e->getMessage(),
        ]));
      }
    }
  }
}
