<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Base class for the fallback.
 *
 * @package Drupal\wateraid_donation_forms
 */
abstract class FallbackBase extends PluginBase implements FallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->getPluginDefinition()['description'];
  }

}
