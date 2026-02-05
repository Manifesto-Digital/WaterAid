<?php

namespace Drupal\azure_blob_storage\Plugin\WebformHandler;

use Drupal\Core\Queue\QueueInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "azure_blob_storage_handler",
 *   label = @Translation("Azure Blob Storage"),
 *   category = @Translation("azure"),
 *   description = @Translation("Transfers webform submissions to the Azure Blob storage."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class AzureWebformHandler extends WebformHandlerBase {

  /**
   * The 'azure_blob_storage_queue'.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  private readonly QueueInterface $queue;

  /**
   * The Request service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): AzureWebformHandler {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->queue = $container->get('queue')->get('azure_blob_storage_queue');
    $instance->requestStack = $container->get('request_stack');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE): void {

    $sid = $webform_submission->id();
    $webform = $webform_submission->getWebform();

    // Add the submission data to the queue so it can be restructured and pushed
    // to the Azure Blob Storage in a separate process that won't slow down the
    // confirmation page displaying.
    $item = [
      'webform_id' => $webform->id(),
      'sid' => $sid,
      'tries' => 0,
    ];

    $this->queue->createItem($item);

    // Now let's store the submission count for later.
    if ($webform_submission->isCompleted()) {
      $uid = $webform_submission->getOwnerId();

      // For the anon user, the IP address is the key.
      $uid = ($uid > 0) ? $uid : $this->requestStack->getCurrentRequest()->getClientIp();

      // If the settings have been overridden we won't be able to save our third
      // party settings, so we'll capture the overridden settings and put them
      // back when we're done.
      $settings = NULL;

      if ($webform->isOverridden()) {
        $settings = $webform->getSettings();
        $webform->resetSettings();
      }

      if (!$submissions = $webform->getThirdPartySetting('wateraid_forms', 'submissions')) {
        $submissions = [
          'total' => [],
          'per_entity' => [],
          'per_user' => [],
          'per_user_per_entity' => [],
        ];
      }

      // Add to the total submissions: key by submission ID to prevent
      // double counting if a submission is edited.
      $submissions['total'][$sid] = 1;
      $submissions['per_user'][$uid][$sid] = 1;

      // If we have a source entity, add to those totals too.
      if ($entity = $webform_submission->getSourceEntity()) {
        $entity_id = $entity->id();
        $submissions['per_entity'][$entity_id][$sid] = 1;
        $submissions['per_user_per_entity'][$uid][$entity_id][$sid] = 1;
      }

      $webform->setThirdPartySetting('wateraid_forms', 'submissions', $submissions);
      $webform->save();

      // If settings were overridden, we can put them back now.
      if ($settings) {
        $webform->setSettingsOverride($settings);
      }
    }
  }

}
