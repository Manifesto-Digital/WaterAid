<?php

namespace Drupal\loqate_email\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\loqate_email\ValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for verifying email addresses.
 */
class ValidatorController extends ControllerBase {

  /**
   * Loqate validator service.
   */
  private ValidatorInterface $validatorService;

  /**
   * Override constructor.
   */
  public function __construct(ValidatorInterface $validatorService) {
    $this->validatorService = $validatorService;
  }

  /**
   * Create method.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('loqate_email.validator'),
    );
  }

  /**
   * Page callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   Json formatted response:
   *     - TRUE if the email validation passed.
   *     - String containing an error message if validation failed.
   */
  public function validate(Request $request): CacheableJsonResponse {
    // Display 404 if this is not an Ajax request.
    if (!$request->isXmlHttpRequest()) {
      throw new NotFoundHttpException();
    }

    $email = $request->query->get('email');
    $refuseDisposable = strtolower($request->query->get('refuseDisposable')) == 'true';

    // Call validator service to check the email address format and validity.
    $service_response = $this->validatorService->validateEmailAddress($email, $refuseDisposable);

    // Return Json encoded response data.
    $response = new CacheableJsonResponse();

    // Response caching to reduce Loqate API calls.
    $metadata = new CacheableMetadata();
    $metadata->addCacheContexts(['url.query_args']);
    $metadata->addCacheTags($this->validatorService->getCacheTags());
    $response->addCacheableDependency($metadata);

    if ($service_response['valid'] === TRUE) {
      // Return a valid response and hash to indicate that
      // an API call was made to Loqate.
      $response_array = [
        'valid' => TRUE,
        'hash' => $service_response['hash'],
      ];
    }
    elseif ($service_response['skipped'] === TRUE) {
      // Return a valid response without a hash to indicate that
      // the value should be accepted, but a loqate API call was not
      // executed.
      $response_array = [
        'valid' => TRUE,
      ];
    }
    else {
      // Reject the email and provide an error message.
      $response_array = [
        'valid' => FALSE,
        'message' => $service_response['invalid_email_error_message'],
      ];
    }
    $response->setJson(Json::encode($response_array));

    return $response;
  }

}
