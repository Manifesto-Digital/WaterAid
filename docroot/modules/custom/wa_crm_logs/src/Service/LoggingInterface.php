<?php

declare(strict_types=1);

namespace Drupal\wa_crm_logs\Service;

use Drupal\wa_crm_logs\CRMLogInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the Logging service.
 */
interface LoggingInterface {

  /**
   * Helper to create a new log entity.
   *
   * @param \Exception|string $error
   *   The error to record.
   * @param \Drupal\webform\WebformSubmissionInterface|int|string $submission
   *   The linked webform submission, or its id.
   * @param \Drupal\group\Entity\GroupInterface|null $group
   *   The group the webform is linked to.
   *
   * @return \Drupal\wa_crm_logs\CRMLogInterface
   *   A new CRMLog entity.
   */
  public function createLog(\Exception|string $error, WebformSubmissionInterface|int|string $submission, ?GroupInterface $group = NULL): CRMLogInterface;

  /**
   * Deletes outdated logs based on settings.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteLogs(): void;

}
