<?php

namespace Drupal\wateraid_azure_storage\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\wateraid_azure_storage\AzureStorageQueueWebformServiceInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send Webform Submissions to Azure Storage Queue action.
 *
 * @Action(
 *   id = "views_bulk_operations_azure_storage_queue",
 *   label = @Translation("Send selected Webform Submissions to an Azure Storage Queue"),
 *   type = "webform_submission",
 *   confirm = TRUE,
 * )
 */
class AzureStorageQueueAction extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {

  /**
   * Azure Storage Queue Webform service.
   */
  protected AzureStorageQueueWebformServiceInterface $azureStorageQueueWebformService;

  /**
   * Logger service.
   */
  protected LoggerChannelInterface $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AzureStorageQueueWebformServiceInterface $azure_storage_queue_webform_service, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->azureStorageQueueWebformService = $azure_storage_queue_webform_service;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('wateraid_azure_storage.webform_service'),
      $container->get('logger.channel.wateraid_azure_storage')
    );
  }

  /**
   * {@inheritdoc}
   *
   * We are specifying the context results here so to ensure that the redirect
   * to the Webform path takes place and not the 1 from the View page. See also
   * ::execute().
   *
   * @see https://www.drupal.org/project/views_bulk_operations/issues/3042535#comment-13055555
   */
  public function setContext(array &$context): void {
    parent::setContext($context);
    $this->context['results'] = &$context['results'];
  }

  /**
   * {@inheritdoc}
   */
  public function execute(?WebformSubmissionInterface $webform_submission = NULL): void {
    if ($webform_submission) {
      try {
        $this->azureStorageQueueWebformService->postWebformSubmission($webform_submission, 'manual');
      }
      catch (\Exception $e) {
        // All exceptions to be logged.
        $this->logger->error('Submission ID: %sid, Error: %error', [
          '%sid' => $webform_submission->id(),
          '%error' => $e->getMessage(),
        ]);
      }
    }

    // @see https://www.drupal.org/project/views_bulk_operations/issues/3042535#comment-13055555
    $this->context['results']['redirect_url'] = Url::fromRoute('wateraid_azure_storage.webform_azure_settings', [
      'webform' => $webform_submission->getWebform()->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE): AccessResultReasonInterface|bool|AccessResult|AccessResultInterface {
    if ($account && $object->getEntityTypeId() === 'webform_submission') {
      $access = AccessResult::allowedIfHasPermission($account, 'administer azure storage');
      return $return_as_object ? $access : $access->isAllowed();
    }
    return FALSE;
  }

}
