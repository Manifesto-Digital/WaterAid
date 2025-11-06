<?php

namespace Drupal\wa_orange_dam\Plugin\media\Source;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\Attribute\MediaSource;

/**
 * DAM Image entity media source.
 */
#[MediaSource(
  id: "dam_image",
  label: new TranslatableMarkup("DAM Image"),
  description: new TranslatableMarkup("Use Orange DAM images for reusable media."),
  allowed_field_types: ["wa_orange_dam_image"],
  default_thumbnail_filename: "no-thumbnail.png",
  thumbnail_alt_metadata_attribute: "thumbnail_alt_value",
  forms: [
    "media_library_add" => "Drupal\wa_orange_dam\Form\AjaxMediaForm",
  ]
)]
final class DamImage extends DamBase {

  /**
   * Returns the local URI for a resource thumbnail.
   *
   * If the thumbnail is not already locally stored, this method will attempt
   * to download it.
   *
   * @param string $system_identifier
   *   The URl of the thumbnail.
   *
   * @return string|null
   *   The local thumbnail URI, or NULL if it could not be downloaded, or if the
   *   resource has no thumbnail at all.
   */
  protected function getLocalThumbnailUri(string $system_identifier): ?string {
    $remote_thumbnail_url = NULL;

    // If there is no remote thumbnail, there's nothing for us to fetch here.
    if ($api_result = $this->orange_api->getPublicLink($system_identifier, NULL, 100, 100)) {
      if (isset($api_result['link'])) {
        $remote_thumbnail_url = $api_result['link'];
      }
    }

    if (!$remote_thumbnail_url) {
      return NULL;
    }

    $directory = 'public://orange_dam_thumbnails';

    // The local thumbnail doesn't exist yet, so try to download it. First,
    // ensure that the destination directory is writable, and if it's not,
    // log an error and bail out.
    if (!$this->file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      $this->logger->warning('Could not prepare thumbnail destination directory @dir for Orange DAM media.', [
        '@dir' => $directory,
      ]);
      return NULL;
    }

    // The local filename of the thumbnail is always a hash of its remote URL.
    // If a file with that name already exists in the thumbnails directory,
    // regardless of its extension, return its URI.
    $hash = Crypt::hashBase64($remote_thumbnail_url);
    $files = $this->file_system->scanDirectory($directory, "/^$hash\..*/");
    if (count($files) > 0) {
      return reset($files)->uri;
    }

    // The local thumbnail doesn't exist yet, so we need to create it.
    try {
      $path = parse_url($remote_thumbnail_url, PHP_URL_PATH);
      $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

      $contents = file_get_contents($remote_thumbnail_url);

      $local_thumbnail_uri = $directory . DIRECTORY_SEPARATOR . $hash . '.' . $extension;
      $this->file_system->saveData($contents, $local_thumbnail_uri, FileExists::Replace);

      return $local_thumbnail_uri;
    }
    catch (FileException $e) {
      $this->logger->warning('Could not download remote thumbnail from {url}.', [
        'url' => $remote_thumbnail_url,
      ]);
    }

    return NULL;
  }

}
