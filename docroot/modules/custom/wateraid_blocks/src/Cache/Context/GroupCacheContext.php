<?php

declare(strict_types=1);

namespace Drupal\wateraid_blocks\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Cache\Context\RequestStackCacheContextBase;
use Drupal\Core\Routing\RouteMatch;

/**
 * Cache context per group.
 *
 * Cache context ID: 'group'.
 *
 * @DCG
 * Check out the core/lib/Drupal/Core/Cache/Context directory for examples of
 * cache contexts provided by Drupal core.
 */
final class GroupCacheContext extends RequestStackCacheContextBase implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel(): string {
    return (string) t('Group');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($parameter = NULL): string {
    $route = RouteMatch::createFromRequest($this->requestStack->getCurrentRequest());

    $group = $route->getParameters()->get('group');

    return ($group) ? $group->id() : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($parameter = NULL): CacheableMetadata {
    return new CacheableMetadata();
  }

}
