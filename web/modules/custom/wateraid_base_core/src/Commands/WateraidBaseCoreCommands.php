<?php

namespace Drupal\wateraid_base_core\Commands;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\wateraid_base_core\Service\WateraidBaseCore;
use Drush\Commands\DrushCommands;

/**
 * Wateraid base core - Drush commands.
 */
class WateraidBaseCoreCommands extends DrushCommands {

  use MessengerTrait;

  /**
   * Constructor.
   *
   * @param \Drupal\wateraid_base_core\Service\WateraidBaseCore $wateraidBaseCore
   *   The wateraid_base_core service.
   */
  public function __construct(
    private WateraidBaseCore $wateraidBaseCore,
  ) {
    parent::__construct();
  }

  /**
   * Display the deduced Google language code for the current subsite.
   *
   * @command wateraid_base_core:get_subsite_langage
   *
   * @usage wateraid_base_core:get_subsite_langage
   */
  public function getSubsiteLanguage() : void {
    $this->messenger()->addMessage($this->getWateraidBaseCore()->getSubsiteLanguage());
  }

  /**
   * Get wateraid_base_core service.
   *
   * @return \Drupal\wateraid_base_core\Service\WateraidBaseCore
   *   The wateraid_base_core service.
   */
  protected function getWateraidBaseCore(): WateraidBaseCore {
    return $this->wateraidBaseCore;
  }

}
