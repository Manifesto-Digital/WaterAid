<?php

declare(strict_types=1);

namespace Drupal\wateraid_donation_forms\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for WaterAid Donation Forms routes.
 */
final class WateraidDonationFormsReminder extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('request_stack'),
    );
  }

  /**
   * Clear the session.
   */
  public function __invoke(): JsonResponse {
    $result = Json::encode([$this->requestStack->getCurrentRequest()->getSession()->remove('wa_donation')]);

    return new JsonResponse($result);
  }

}
