<?php

declare(strict_types=1);

namespace Drupal\wa_crm_logs\Service;

use Drupal\wa_crm_logs\CRMLogInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Service to assist with functions relating to logging CRM errors.
 */
final class Logging implements LoggingInterface {

  /**
   * Constructs a Logging object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function createLog(\Exception|string $error, WebformSubmissionInterface|int|string $submission, ?GroupInterface $group = NULL): CRMLogInterface {

    /** @var \Drupal\wa_crm_logs\CRMLogInterface $log */
    $log = $this->entityTypeManager->getStorage('crm_log')->create([
      'submission' => $submission,
      'description' => (is_string($error)) ? $error : $error->getMessage(),
    ]);

    if ($group) {
      $log->set('group', $group);
    }

    return $log;
  }

}
