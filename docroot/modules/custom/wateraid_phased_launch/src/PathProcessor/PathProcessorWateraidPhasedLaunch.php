<?php

declare(strict_types=1);

namespace Drupal\wateraid_phased_launch\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor to ensure any hardcoded calls to /views/ajax use the new path.
 */
final class PathProcessorWateraidPhasedLaunch implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request): string {
    return $this->alterPath($path);
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL): string {
    return $this->alterPath($path);
  }

  /**
   * Alter paths so they end up going to the correct server.
   *
   * @param string $path
   *   The path to alter.
   *
   * @return string
   *   The altered path or the original if no change required.
   */
  private function alterPath(string $path): string {
    foreach ($this->getPaths() as $old) {
      if ($path == $old) {
        $path = '/wateraid-donation-v2' . $old;
      }
    }

    return $path;
  }

  /**
   * Helper to get the paths that need altering.
   *
   * @return string[]
   *   An array of paths.
   */
  private function getPaths(): array {
    return [
      '/views/ajax',
      '/media/oembed',
    ];
  }

}
