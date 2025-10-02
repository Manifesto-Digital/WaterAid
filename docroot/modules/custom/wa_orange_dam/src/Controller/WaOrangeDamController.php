<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Orange DAM routes.
 */
final class WaOrangeDamController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly StateInterface $state,
    private readonly RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('state'),
      $container->get('request_stack'),
    );
  }

  /**
   * Check for an authorization code and store it.
   */
  public function __invoke(): RedirectResponse {

    // If we've made it here, then the state token validated: we should delete
    // it now so it can't be used again.
    $this->state->delete('wa_orange_dam_authorize_token');

    // Next we need to grab the code and store it.
    if ($code = $this->requestStack->getCurrentRequest()->query->get('code')) {
      $this->state->set('wa_orange_dam_auth_code', $code);
      $this->messenger()->addStatus($this->t('The access code has been received and stored. Thank you.'));
    }
    else {
      $this->messenger()->addError($this->t('Something went wrong. Please try again: if the problem persists please report it to an administrator.'));
    }

    return $this->redirect('wa_orange_dam.oauth2');
  }

}
