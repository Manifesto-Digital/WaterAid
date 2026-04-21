<?php

declare(strict_types=1);

namespace Drupal\wa_crm_logs;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the crm log entity type.
 */
final class CRMLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    return [
      '#type' => 'view',
      '#name' => 'crm_logs',
      '#display_id' => 'logs',
    ];
  }

}
