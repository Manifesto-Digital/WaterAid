<?php

namespace Drupal\wateraid_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a global site footer block.
 *
 * @Block(
 *   id = "wateraid_site_footer",
 *   admin_label = @Translation("WaterAid: Site footer"),
 *   category = @Translation("WaterAid blocks")
 * )
 */
class SiteFooterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => '<h1>WaterAid site footer block</h1>',
    ];
  }

}
