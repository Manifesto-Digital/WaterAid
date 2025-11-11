<?php

namespace Drupal\wateraid_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a global search hero block.
 *
 * @Block(
 *   id = "wateraid_search_hero",
 *   admin_label = @Translation("WaterAid: Search hero"),
 *   category = @Translation("WaterAid blocks")
 * )
 */
class SearchHeroBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => '<h1>WaterAid search hero block</h1>',
    ];
  }

}
