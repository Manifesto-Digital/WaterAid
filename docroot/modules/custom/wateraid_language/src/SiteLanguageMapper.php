<?php

namespace Drupal\wateraid_language;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Service for managing site-language mappings.
 */
class SiteLanguageMapper {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a SiteLanguageMapper object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Get the mapping configuration.
   *
   * @return array
   *   The site language mapping configuration.
   */
  public function getMappings(): array {
    $config = $this->configFactory->get('wateraid_language.site_language_map');
    return $config->getRawData() ?: [];
  }

  /**
   * Get the prefix for a specific group and language combination.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param string $langcode
   *   The language code.
   *
   * @return string|null
   *   The URL prefix or NULL if not found.
   */
  public function getPrefix(GroupInterface $group, string $langcode): ?string {
    $mappings = $this->getMappings();
    $group_label = $group->label();

    foreach ($mappings as $key => $mapping) {
      if (isset($mapping['group_label']) && $mapping['language']) {
        if ($mapping['group_label'] === $group_label && $mapping['language'] === $langcode) {
          return $mapping['prefix'] ?? NULL;
        }
      }
    }

    return NULL;
  }

  /**
   * Get the group and language from a URL prefix.
   *
   * @param string $prefix
   *   The URL prefix (e.g., 'jp', 'in/hi').
   *
   * @return array|null
   *   Array with 'group_label' and 'language' or NULL if not found.
   */
  public function getGroupLanguageFromPrefix(string $prefix): ?array {
    $mappings = $this->getMappings();

    foreach ($mappings as $key => $mapping) {
      if (isset($mapping['prefix']) && $mapping['prefix'] === $prefix) {
        return [
          'group_label' => $mapping['group_label'] ?? NULL,
          'language' => $mapping['language'] ?? NULL,
          'skip_language_prefix' => $mapping['skip_language_prefix'] ?? FALSE,
        ];
      }
    }

    return NULL;
  }

  /**
   * Check if a language prefix should be skipped for a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   TRUE if the language prefix should be skipped.
   */
  public function shouldSkipLanguagePrefix(GroupInterface $group, string $langcode): bool {
    $mappings = $this->getMappings();
    $group_label = $group->label();

    foreach ($mappings as $key => $mapping) {
      if (isset($mapping['group_label']) && $mapping['language']) {
        if ($mapping['group_label'] === $group_label && $mapping['language'] === $langcode) {
          return $mapping['skip_language_prefix'] ?? FALSE;
        }
      }
    }

    return FALSE;
  }

}
