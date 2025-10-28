<?php

namespace Drupal\wateraid_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a donate footer block.
 *
 * @Block(
 *   id = "wateraid_donate_footer",
 *   admin_label = @Translation("WaterAid: Donate footer"),
 *   category = @Translation("WaterAid blocks")
 * )
 */
class DonateFooterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => '<h1>WaterAid donate footer block</h1>',
    ];
  }

}
