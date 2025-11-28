<?php

namespace Drupal\wateraid_language\Plugin\LanguageNegotiation;

use Drupal\Core\PathProcessor\PathProcessorManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\language\LanguageNegotiationMethodBase;
use Drupal\wateraid_language\SiteLanguageMapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Language negotiation based on WaterAid site-language mappings.
 *
 * @LanguageNegotiation(
 *   id = "wateraid-site-language",
 *   name = @Translation("WaterAid Site-Language URL"),
 *   description = @Translation("Determines language from URL prefix based on site-language mappings (e.g., /jp, /in/hi)."),
 *   weight = -9
 * )
 */
class WateraidSiteLanguageNegotiation extends LanguageNegotiationMethodBase implements ContainerFactoryPluginInterface {

  /**
   * The site language mapper service.
   *
   * @var \Drupal\wateraid_language\SiteLanguageMapper
   */
  protected SiteLanguageMapper $siteLanguageMapper;

  /**
   * The path processor manager.
   *
   * @var \Drupal\Core\PathProcessor\PathProcessorManager
   */
  protected PathProcessorManager $pathProcessorManager;

  /**
   * Constructs a new WateraidSiteLanguageNegotiation instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\wateraid_language\SiteLanguageMapper $site_language_mapper
   *   The site language mapper service.
   * @param \Drupal\Core\PathProcessor\PathProcessorManager $path_processor_manager
   *   The path processor manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    SiteLanguageMapper $site_language_mapper,
    PathProcessorManager $path_processor_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->siteLanguageMapper = $site_language_mapper;
    $this->pathProcessorManager = $path_processor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('wateraid_language.site_language_mapper'),
      $container->get('path_processor_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL): ?string {
    if (!$request) {
      return NULL;
    }

    $path = $request->getPathInfo();

    // Remove leading slash.
    $path = ltrim($path, '/');

    // Split path into segments.
    $segments = explode('/', $path);

    if (empty($segments[0])) {
      return NULL;
    }

    // Try to match single prefix (e.g., "jp", "in", "uk").
    $prefix = $segments[0];
    $mapping = $this->siteLanguageMapper->getGroupLanguageFromPrefix($prefix);

    if ($mapping) {
      return $mapping['language'];
    }

    // Try to match double prefix (e.g., "in/hi").
    if (isset($segments[1])) {
      $double_prefix = $segments[0] . '/' . $segments[1];
      $mapping = $this->siteLanguageMapper->getGroupLanguageFromPrefix($double_prefix);

      if ($mapping) {
        return $mapping['language'];
      }
    }

    return NULL;
  }

}
