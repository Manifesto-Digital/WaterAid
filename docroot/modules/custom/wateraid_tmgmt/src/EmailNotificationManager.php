<?php

namespace Drupal\wateraid_tmgmt;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;

/**
 * Contains the email notification manager service.
 *
 * @package Drupal\wateraid_tmgmt
 */
class EmailNotificationManager {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * Number of error messages to extract per run.
   */
  const MAX_ERRORS_PER_RUN = 1000;

  /**
   * Truncate error message text over this limit.
   */
  const ERROR_TRUNCATION_LIMIT = 2000;

  /**
   * Configuration settings name.
   */
  const CONFIG_SETTINGS = 'wateraid_tmgmt.settings';

  /**
   * The Config factory.
   */
  protected ConfigFactory $configFactory;

  /**
   * The date formatter service.
   */
  protected DateFormatter $dateFormatter;

  /**
   * Entity type manager.
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * Mail manager.
   */
  protected MailManager $mailManager;

  /**
   * Render service.
   */
  protected Renderer $renderer;

  /**
   * Constructs the EmailNotificationManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Mail\MailManager $mailManager
   *   Mail manager.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Render service.
   */
  public function __construct(
    ConfigFactory $configFactory,
    DateFormatterInterface $dateFormatter,
    EntityTypeManager $entityTypeManager,
    MailManager $mailManager,
    Renderer $renderer,
  ) {
    $this->configFactory = $configFactory;
    $this->dateFormatter = $dateFormatter;
    $this->entityTypeManager = $entityTypeManager;
    $this->mailManager = $mailManager;
    $this->renderer = $renderer;
  }

  /**
   * Generate and send email notifications.
   */
  public function generateNotificationEmails(): void {
    if ($this->configFactory->get(self::CONFIG_SETTINGS)->get('enable_email_notifications') !== TRUE) {
      // Do not send emails if notifications are disabled.
      return;
    }

    $notification_build = [];
    $this->getNewErrors($notification_build);
    $this->getStuckJobs($notification_build);
    $this->getStuckJobItems($notification_build);

    if (!empty($notification_build)) {
      foreach ($notification_build as $user_id => $params) {
        $email = [];

        /** @var \Drupal\user\Entity\User $recipient */
        $recipient = $this->entityTypeManager->getStorage('user')->load($user_id);
        $recipient_email = $recipient->getEmail();

        // Generate stuck jobs table.
        if (array_key_exists('stuck_jobs', $params)) {
          $email['build']['stuck_jobs'] = [
            '#type' => 'table',
            '#prefix' => '<h2>Unprocessed jobs</h2>',
            '#header' => [
              $this->t('Job number'),
              $this->t('Status'),
              $this->t('Last updated'),
              $this->t('Link'),
            ],
          ];

          foreach ($params['stuck_jobs'] as $row) {
            $email['build']['stuck_jobs']['#rows'][] = $row;
          }
        }

        // Generate stuck job items table.
        if (array_key_exists('stuck_job_items', $params)) {
          $email['build']['stuck_job_items'] = [
            '#type' => 'table',
            '#prefix' => '<h2>Unprocessed job items</h2>',
            '#header' => [
              $this->t('Job item number'),
              $this->t('Status'),
              $this->t('Last updated'),
              $this->t('Link'),
            ],
          ];

          foreach ($params['stuck_job_items'] as $row) {
            $email['build']['stuck_job_items']['#rows'][] = $row;
          }
        }

        // Generate erroring jobs table.
        if (array_key_exists('errors', $params)) {
          $email['build']['errors'] = [
            '#type' => 'table',
            '#prefix' => '<h2>Erroring jobs/job items</h2>',
            '#header' => [
              $this->t('Job/job item number'),
              $this->t('Status'),
              $this->t('Error message'),
              $this->t('Date/time'),
              $this->t('Link'),
            ],
          ];

          foreach ($params['errors'] as $error_row) {
            $email['build']['errors']['#rows'][] = $error_row;
          }
        }

        if (!empty($email)) {
          $rendered_email['body'] = $this->renderer->renderRoot($email);
          $this->mailManager->mail('wateraid_tmgmt', 'wateraid_tmgmt_error_notification', $recipient_email, NULL, $rendered_email);
        }
      }
    }
  }

  /**
   * Find translation error messages that have not been sent via email yet.
   *
   * @param mixed[] $notification_build
   *   Drupal build array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function getNewErrors(array &$notification_build): void {
    $message_storage = $this->entityTypeManager->getStorage('tmgmt_message');
    $error_query = $message_storage->getQuery();
    $error_query->condition('type', 'error');
    $error_query->notExists('email_notification_timestamp');
    $error_query->range(0, self::MAX_ERRORS_PER_RUN);
    $error_messages = $error_query->execute();
    foreach ($error_messages as $error_message_id) {
      /** @var \Drupal\tmgmt\Entity\Message $message */
      $message = $message_storage->load($error_message_id);
      $job = $message->getJob();
      $state = $this->getStateLabel($job);
      if (!empty($message->get('tjiid')->getValue())) {
        $job_item = $message->getJobItem();
        $state = $this->getStateLabel($job_item);
      }

      $created = $this->dateFormatter->format($message->get('created')->value);

      $user_id = $message->getJob()->getOwnerId();
      $notification_build[$user_id]['errors'][] = [
        'job_id' => isset($job_item) ? $this->t('@job_id (@job_item_id)', [
          '@job_id' => $job->id(),
          '@job_item_id' => $job_item->id(),
        ]) : $job->id(),
        'status' => $state,
        'error' => substr($message->getMessage(), 0, self::ERROR_TRUNCATION_LIMIT),
        'date_time' => $created,
        'link' => isset($job_item) ? $job_item->toLink('Job item') : $job->toLink('Job'),
      ];

      // Update the message to prevent multiple email notifications.
      $now = new DrupalDateTime();
      try {
        $message->set('email_notification_timestamp', $now->getTimestamp());
        $message->save();
      }
      catch (\Exception $e) {
        $this->getLogger('wateraid_tmgmt')->error('Unable to set email_notification_timestamp on TMGMT error @message_id', [
          '@message_id' => $message->id(),
        ]);
      }
    }
  }

  /**
   * Gets the job or job item state label.
   *
   * @param \Drupal\tmgmt\JobInterface|\Drupal\tmgmt\JobItemInterface $entity
   *   The job or job item entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|mixed
   *   The state label.
   */
  protected function getStateLabel(JobInterface|JobItemInterface $entity): mixed {
    $state_id = $entity->getState();

    if ($entity instanceof JobInterface) {
      $states = Job::getStates();
    }
    else {
      $states = JobItem::getStates();
    }

    if (isset($states) && array_key_exists($state_id, $states)) {
      return $states[$state_id];
    }
    else {
      return $this->t('Unknown');
    }
  }

  /**
   * Find translation jobs that are stuck in the system.
   *
   * @param mixed[] $notification_build
   *   Drupal build array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function getStuckJobs(array &$notification_build): void {
    $job_storage = $this->entityTypeManager->getStorage('tmgmt_job');
    $stuck_job_query = $job_storage->getQuery();
    $stuck_job_query->condition('state', JobInterface::STATE_ACTIVE);

    // Only notify if the last update was more than a week ago.
    $now = new DrupalDateTime();
    $one_week_ago = $now->modify('-1 week')->getTimestamp();
    $stuck_job_query->condition('changed', $one_week_ago, '<=');

    $stuck_jobs = $stuck_job_query->execute();
    foreach ($stuck_jobs as $stuck_job_id) {
      /** @var \Drupal\tmgmt\Entity\Job $job */
      $job = $job_storage->load($stuck_job_id);
      $changed = $this->dateFormatter->format($job->getChangedTime());
      $user_id = $job->getOwnerId();
      $notification_build[$user_id]['stuck_jobs'][] = [
        'job_id' => $job->id(),
        'status' => $this->getStateLabel($job),
        'updated' => $changed,
        'link' => $job->toLink('Job'),
      ];
    }
  }

  /**
   * Find translation job items that are stuck in the system.
   *
   * @param mixed[] $notification_build
   *   Drupal build array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function getStuckJobItems(array &$notification_build): void {
    $job_item_storage = $this->entityTypeManager->getStorage('tmgmt_job_item');
    $stuck_job_item_query = $job_item_storage->getQuery();
    $stuck_job_item_states = [
      JobItemInterface::STATE_ACTIVE,
      JobItemInterface::STATE_REVIEW,
    ];
    $stuck_job_item_query->condition('state', $stuck_job_item_states, 'IN');

    // Only notify if the last update was more than a week ago.
    $now = new DrupalDateTime();
    $one_week_ago = $now->modify('-1 week')->getTimestamp();
    $stuck_job_item_query->condition('changed', $one_week_ago, '<=');

    $stuck_job_items = $stuck_job_item_query->execute();
    foreach ($stuck_job_items as $stuck_job_item_id) {

      /** @var \Drupal\tmgmt\Entity\JobItem $job_item */
      $job_item = $job_item_storage->load($stuck_job_item_id);
      $changed = $this->dateFormatter->format($job_item->getChangedTime());
      $user_id = $job_item->getJob()->getOwnerId();
      $notification_build[$user_id]['stuck_job_items'][] = [
        'job_item_id' => $job_item->id(),
        'status' => $this->getStateLabel($job_item),
        'updated' => $changed,
        'link' => $job_item->toLink('Job item'),
      ];
    }
  }

}
