<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Checks if passed parameter matches the route configuration.
 */
final class TokenAccessChecker implements AccessInterface {

  /**
   * Constructs a TokenAccessChecker object.
   */
  public function __construct(
    private readonly StateInterface $state,
    private readonly RequestStack $requestStack,
  ) {}

  /**
   * Access callback.
   */
  public function access(): AccessResult {
    $sent = $this->state->get('wa_orange_dam_authorize_token');
    $received = $this->requestStack->getCurrentRequest()->query->get('state');

    return AccessResult::allowedIf($received == $sent);
  }

}
