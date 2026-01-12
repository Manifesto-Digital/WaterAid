<?php

namespace Drupal\wateraid_group\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a cached mapping of WaterAid site slugs to available languages.
 */
final class GroupLanguageMap {

  /**
   * Cache ID for the group language map.
   */
  private const CACHE_ID = 'wateraid_group.group_language_map';

  /**
   * Constructs a new GroupLanguageMap service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly CacheBackendInterface $cache,
  ) {}

  /**
   * Returns the cached group language map, rebuilding if necessary.
   *
   * @return array
   *   An associative array keyed by group slug containing language metadata.
   */
  public function getMap(): array {
    if ($cache = $this->cache->get(self::CACHE_ID)) {
      return $cache->data;
    }
    return $this->rebuild();
  }

  /**
   * Gets the language metadata for a specific slug.
   *
   * @param string $slug
   *   The group slug.
   *
   * @return array|null
   *   The metadata array or NULL if the slug is unknown.
   */
  public function getSlugData(string $slug): ?array {
    $map = $this->getMap();
    return $map[$slug] ?? NULL;
  }

  /**
   * Clears the cached language map.
   */
  public function reset(): void {
    $this->cache->delete(self::CACHE_ID);
  }

  /**
   * Rebuilds the cached mapping from current group entities.
   *
   * @return array
   *   The rebuilt mapping.
   */
  public function rebuild(): array {
    $storage = $this->entityTypeManager->getStorage('group');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'wateraid_site');
    $group_ids = $query->execute();

    $map = [];
    $tags = ['group_list'];

    if ($group_ids) {
      /** @var \Drupal\group\Entity\GroupInterface[] $groups */
      $groups = $storage->loadMultiple($group_ids);
      foreach ($groups as $group) {
        if (!$group->hasField('field_slug') || $group->get('field_slug')->isEmpty()) {
          continue;
        }
        $slug = (string) $group->get('field_slug')->value;
        if ($slug === '') {
          continue;
        }

        $languages = array_keys($group->getTranslationLanguages());
        if (!$languages) {
          $languages[] = $group->language()->getId();
        }
        $languages = array_values(array_unique($languages));

        $map[$slug] = [
          'languages' => $languages,
          'default' => $this->determineDefaultLanguage($languages),
        ];
        $tags[] = 'group:' . $group->id();
      }
    }

    $this->cache->set(self::CACHE_ID, $map, CacheBackendInterface::CACHE_PERMANENT, array_values(array_unique($tags)));
    return $map;
  }

  /**
   * Determines the default language for a slug based on available languages.
   *
   * @param string[] $languages
   *   Language codes available for the slug.
   *
   * @return string|null
   *   Default language code, or NULL if no default may be assumed.
   */
  private function determineDefaultLanguage(array $languages): ?string {
    if (!$languages) {
      return NULL;
    }
    if (count($languages) === 1) {
      return $languages[0];
    }
    if (in_array('en', $languages, TRUE)) {
      return 'en';
    }
    return NULL;
  }

}
