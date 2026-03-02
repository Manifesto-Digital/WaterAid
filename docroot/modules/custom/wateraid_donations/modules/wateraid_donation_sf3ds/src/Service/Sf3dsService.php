<?php

namespace Drupal\wateraid_donation_sf3ds\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\wateraid_donation_gmo\WateraidWebformGmoService;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Sf3ds - Helper functions.
 */
class Sf3dsService {

  public function __construct(
    private ConfigFactoryInterface $configFactory,
    private RouteMatchInterface $routeMatch,
    private EntityTypeManagerInterface $entityTypeManager,
    private readonly WateraidWebformGmoService $webformGmoService,
  ) {
  }

  /**
   * Get webform submission from route.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   The webform submission (NULL if not found).
   */
  public function getWebformSubmissionFromRoute(): WebformSubmissionInterface|NULL {
    if ($token = $this->getRouteMatch()->getParameter('token')) {
      if ($webform_submissions = $this->getEntityTypeManager()->getStorage('webform_submission')->loadByProperties(['token' => $token])) {
        return array_shift($webform_submissions);
      }
    }
    return NULL;
  }

  /**
   * Get SF3DS form action.
   *
   * Defaults to configured sandbox,
   * unless overridden with environmental variable.
   *
   * @return string
   *   The form action.
   */
  public function getFormAction() : string {
    $action = $this->webformGmoService->getSalesforceUrl();

    return ($_ENV['SF3DS_FORM_ACTION']) ? $_ENV['SF3DS_FORM_ACTION'] . '/paymentrequest' : $action;
  }

  /**
   * Get 'current_route_match' service.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The 'current_route_match' service.
   */
  protected function getRouteMatch(): RouteMatchInterface {
    return $this->routeMatch;
  }

  /**
   * Get 'entity_type.manager' service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The 'entity_type.manager' service.
   */
  protected function getEntityTypeManager(): EntityTypeManagerInterface {
    return $this->entityTypeManager;
  }

  /**
   * Get 'config.factory' service.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The 'config.factory' service.
   */
  private function getConfigFactory(): ConfigFactoryInterface {
    return $this->configFactory;
  }

  /**
   * Get 'sf3ds' config.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The 'sf3ds' config.
   */
  public function getConfig() : ImmutableConfig {
    return $this->getConfigFactory()->get('wateraid_donation_sf3ds.settings');
  }

}
