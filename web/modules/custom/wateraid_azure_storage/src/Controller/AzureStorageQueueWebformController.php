<?php

namespace Drupal\wateraid_azure_storage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Displays Azure WEbform Queue items.
 *
 * @package Drupal\wateraid_azure_storage\Controller
 */
class AzureStorageQueueWebformController extends ControllerBase {

  /**
   * Logger service.
   */
  protected LoggerChannelInterface $logger;

  /**
   * Constructs a StripeController object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger instance.
   */
  public function __construct(LoggerChannelInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('logger.channel.wateraid_azure_storage')
    );
  }

  /**
   * Outputs the webform Azure Storage view.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The page request.
   *
   * @return mixed[]|null
   *   Render array for the view or NULL if not available.
   */
  public function index(Request $request): ?array {

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $request->get('webform');

    /** @var \Drupal\views\ViewExecutable $view */
    $view = Views::getView('webform_azure_storage');

    if ($webform instanceof WebformInterface === FALSE || $view instanceof ViewExecutable === FALSE) {
      $this->logger->error($this->t('Webform Azure Storage View does not exist.'));
      throw new NotFoundHttpException();
    }

    $view->setArguments([$webform->id()]);
    $view->setDisplay('webform_azure_storage');

    return $view->buildRenderable();
  }

}
