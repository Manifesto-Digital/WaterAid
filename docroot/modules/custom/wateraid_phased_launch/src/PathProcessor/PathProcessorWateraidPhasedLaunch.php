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
    if ($path == '/views/ajax') {
      $path = '/wateraid-donation-v2/views/ajax';
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL): string {
    if ($path == '/views/ajax') {
      $path = '/wateraid-donation-v2/views/ajax';
    }

    return $path;
  }

}
