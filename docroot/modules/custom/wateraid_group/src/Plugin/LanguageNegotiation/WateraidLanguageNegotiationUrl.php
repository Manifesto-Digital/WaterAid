<?php

namespace Drupal\wateraid_group\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\language\Attribute\LanguageNegotiation;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\wateraid_group\Service\GroupLanguageMap;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Custom WaterAid language negotiation plugin extending core URL negotiation.
 */
#[LanguageNegotiation(
  id: WateraidLanguageNegotiationUrl::METHOD_ID,
  name: new TranslatableMarkup('URL (WaterAid)'),
  types: [
    LanguageInterface::TYPE_INTERFACE,
    LanguageInterface::TYPE_CONTENT,
    LanguageInterface::TYPE_URL,
  ],
  weight: -9,
  description: new TranslatableMarkup("WaterAid custom language negotiation from URL (Path prefix or domain)."),
  config_route_name: 'language.negotiation_url'
)]
class WateraidLanguageNegotiationUrl extends LanguageNegotiationUrl implements InboundPathProcessorInterface, OutboundPathProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * Cached group language metadata.
   */
  protected GroupLanguageMap $groupLanguageMap;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static();
    $instance->groupLanguageMap = $container->get('wateraid_group.group_language_map');
    return $instance;
  }

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'wateraid-language-url';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(?Request $request = NULL) {

    if (!$request) {
      return parent::getLangcode($request);
    }

    $path = trim($request->getPathInfo() ?? '', '/');
    if ($path === '') {
      return parent::getLangcode($request);
    }

    $segments = explode('/', $path);
    $slug = array_shift($segments);
    if ($slug === NULL || $slug === '') {
      return parent::getLangcode($request);
    }

    $group_language_map = $this->groupLanguageMap->getMap();
    if (!isset($group_language_map[$slug])) {
      return parent::getLangcode($request);
    }

    $group_data = $group_language_map[$slug];
    $candidate = $segments[0] ?? NULL;

    if ($candidate && in_array($candidate, $group_data['languages'], TRUE)) {
      return $candidate;
    }

    if (!empty($group_data['default'])) {
      return $group_data['default'];
    }

    return parent::getLangcode($request);
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $segments = $this->getPathSegments($path);
    if (!$segments) {
      return parent::processInbound($path, $request);
    }

    $slug = $segments[0];
    $group_data = $this->groupLanguageMap->getSlugData($slug);
    if (!$group_data) {
      return parent::processInbound($path, $request);
    }

    if ($this->groupSupportsMultipleLanguages($group_data)) {
      $language_segment = $segments[1] ?? NULL;
      if ($language_segment && in_array($language_segment, $group_data['languages'], TRUE)) {
        // Remove the explicit language segment so aliases remain slug-based.
        array_splice($segments, 1, 1);
      }
    }

    $rewritten = $this->buildPathFromSegments($segments);
    return parent::processInbound($rewritten, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], ?Request $request = NULL, ?BubbleableMetadata $bubbleable_metadata = NULL) {
    $path = parent::processOutbound($path, $options, $request, $bubbleable_metadata);

    $segments = $this->getPathSegments($path);
    if (!$segments) {
      return $path;
    }

    $slug = $segments[0];
    $group_data = $this->groupLanguageMap->getSlugData($slug);
    if (!$group_data) {
      return $path;
    }

    $target_language = $this->resolveTargetLanguageFromOptions($options, $group_data);

    // Prevent the parent from prepending the language prefix globally.
    unset($options['prefix']);

    if ($target_language == 'en') {
      return $this->buildPathFromSegments($segments);
    }

    if (!$this->groupSupportsMultipleLanguages($group_data)) {
      return $this->buildPathFromSegments($segments);
    }

    if (!$target_language || !in_array($target_language, $group_data['languages'], TRUE)) {
      return $this->buildPathFromSegments($segments);
    }

    $existing_language_segment = $segments[1] ?? NULL;
    if ($existing_language_segment && in_array($existing_language_segment, $group_data['languages'], TRUE)) {
      $segments[1] = $target_language;
    }
    else {
      array_splice($segments, 1, 0, [$target_language]);
    }

    return $this->buildPathFromSegments($segments);
  }

  /**
   * Splits a path string into segments.
   */
  private function getPathSegments(string $path): array {
    if ($path === '' || $path === '/') {
      return [];
    }
    if (str_starts_with($path, '<')) {
      return [];
    }
    $trimmed = trim($path, '/');
    return $trimmed === '' ? [] : explode('/', $trimmed);
  }

  /**
   * Reassembles a path string from segments.
   */
  private function buildPathFromSegments(array $segments): string {
    return '/' . implode('/', $segments);
  }

  /**
   * Determines if a group has multiple active languages.
   */
  private function groupSupportsMultipleLanguages(array $group_data): bool {
    return count($group_data['languages'] ?? []) > 1;
  }

  /**
   * Finds the desired language for outbound URLs.
   */
  private function resolveTargetLanguageFromOptions(array $options, array $group_data): ?string {
    if (!empty($options['language'])) {
      $language_option = $options['language'];
      if ($language_option instanceof LanguageInterface) {
        return $language_option->getId();
      }
      if (is_string($language_option)) {
        return $language_option;
      }
    }

    return $group_data['default'] ?? NULL;
  }

}
