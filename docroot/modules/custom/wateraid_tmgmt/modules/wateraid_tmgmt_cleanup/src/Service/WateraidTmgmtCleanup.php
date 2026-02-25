<?php

namespace Drupal\wateraid_tmgmt_cleanup\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;

/**
 * Wateraid Tmgmt Cleanup functions.
 */
class WateraidTmgmtCleanup {

  /**
   * Constructs the EmailNotificationManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The 'entity_type.manager' service.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The 'queue' service.
   */
  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    private QueueFactory $queueFactory,
  ) {}

  /**
   * Delete translation files (via queue).
   *
   * @param int $older_than_days
   *   Delete only fiels older than $older_than_days days.
   * @param int $max
   *   Maximum number of files to inspect.
   */
  public function deleteTranslationFiles(int $older_than_days = 7, int $max = 50000): void {

    // Reset queue each time this method is called.
    $queue = $this->getQueue();
    $queue->deleteQueue();
    $queue->createQueue();

    /** @var \Drupal\Core\Entity\Query\Sql\Query $query */
    $query = $this->getEntityTypeManager()->getStorage('file')->getQuery()->accessCheck(FALSE);
    $query->condition('uri', 'public://tmgmt_smartling_context/resources%', 'LIKE');
    $changed = time() - ($older_than_days * 24 * 60 * 60);
    $query->condition('changed', $changed, '<=');
    $results = $query->execute();

    // Add each file to the queue for deletion.
    $qty = 0;
    foreach ($results as $fid) {
      $queue->createItem($fid);
      if (++$qty >= $max) {
        break;
      }
    }
  }

  /**
   * Get 'wateraid_tmgmt_cleanup' queue.
   *
   * @return \Drupal\Core\Queue\QueueInterface
   *   The 'wateraid_tmgmt_cleanup' queue.
   */
  protected function getQueue(): QueueInterface {
    return $this->getQueueFactory()->get('wateraid_tmgmt_cleanup_delete_translation_files');
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
   * Get 'queue' service.
   *
   * @return \Drupal\Core\Queue\QueueFactory
   *   The 'queue' service.
   */
  protected function getQueueFactory(): QueueFactory {
    return $this->queueFactory;
  }

}
