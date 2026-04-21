<?php

declare(strict_types=1);

namespace Drupal\wa_crm_logs;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a crm log entity type.
 */
interface CRMLogInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
