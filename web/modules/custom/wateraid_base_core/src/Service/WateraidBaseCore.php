<?php

namespace Drupal\wateraid_base_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the WaterAidBaseCore functionality.
 */
class WateraidBaseCore {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request_stack service.
   */
  public function __construct(
    private ConfigFactoryInterface $config,
    private RequestStack $requestStack,
  ) {}

  /**
   * Helper to get the correct Google language code for the current subsite.
   *
   * @return string
   *   The google language code. Defaults to 'en'.
   */
  public function getSubsiteLanguage(): string {

    // This will get you a string for example: 'sites/default'.
    $site_path = DrupalKernel::findSitePath($this->getRequestStack()->getCurrentRequest());

    // Explode it to only get the 'default' part in the next step.
    $site_path = explode('/', $site_path);

    /*
     * On ddev, site_path looks like sites/se, or sites/in.
     * On Acquia envs, site path looks like sites/g/files/jkxoof226 etc.
     *
     * Check configured site paths (3 for aquia, 1 for ddev) for matching search
     * value (ie jkxoof226 on site path index 3 THEN se on site path index 1).
     *
     * Return map key (ie the google language key) on first find and match.
     */
    if (
      $indexes = $this->getConfig()->get('subsite_language.indexes')
      and $map = $this->getConfig()->get('subsite_language.map')
    ) {
      foreach ($indexes as $search_index => $site_path_index) {

        // Check if our index exists in the site path.
        if (array_key_exists($site_path_index, $site_path)) {

          // Check if our search index for the site path index match,
          // return corresponding key if match found.
          foreach ($map as $return => $search) {
            if ($site_path[$site_path_index] === $search[$search_index]) {
              return $return;
            }
          }
        }
      }
    }

    // Default to en.
    return 'en';
  }

  /**
   * Get config.factory service.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The config.factory service.
   */
  protected function getConfig(): ImmutableConfig {
    return $this->config->get('wateraid_base_core.settings');
  }

  /**
   * Get request_stack service.
   *
   * @return \Symfony\Component\HttpFoundation\RequestStack
   *   The request_stack service.
   */
  protected function getRequestStack(): RequestStack {
    return $this->requestStack;
  }

}
