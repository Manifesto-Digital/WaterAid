<?php

namespace Drupal\wateraid_core\Twig;

use Drupal\wa_orange_dam\Service\Api;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for working with DAM images.
 *
 * This extension integrates with the Orange DAM system to provide image URL
 * generation functionality within Twig templates.
 */
class DamImageTwigExtension extends AbstractExtension {

  /**
   * Constructs a new DamImageTwigExtension.
   *
   * @param \Drupal\wa_orange_dam\Service\Api $damApi
   *   The DAM API service for interacting with Orange DAM.
   */
  public function __construct(
    private readonly Api $damApi,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('dam_image_url', [$this, 'getDamImageUrl']),
    ];
  }

  /**
   * Generates a public URL for a DAM image with optional dimensions.
   *
   * This function takes a DAM field item and generates a public URL for the
   * associated image. Optionally, width and height parameters can be provided
   * to generate a resized version of the image.
   *
   * @param array $dam_field_item
   *   An array containing DAM field data, must include 'system_identifier' key.
   * @param int|null $width
   *   (optional) The desired width for the image in pixels.
   * @param int|null $height
   *   (optional) The desired height for the image in pixels.
   *
   * @return string|null
   *   The public URL for the DAM image, or NULL if the system identifier
   *   is missing or the API call fails.
   */
  public function getDamImageUrl($dam_field_item, $width = NULL, $height = NULL): ?string {
    if (empty($dam_field_item['system_identifier'])) {
      return NULL;
    }

    $data = $this->damApi->getPublicLink(
      $dam_field_item['system_identifier'],
      NULL,
      $width,
      $height
    );

    return $data['link'] ?? NULL;
  }
}