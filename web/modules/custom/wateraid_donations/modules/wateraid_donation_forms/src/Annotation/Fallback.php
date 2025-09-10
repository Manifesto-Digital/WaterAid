<?php

namespace Drupal\wateraid_donation_forms\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a Fallback annotation object.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class Fallback extends Plugin {

  /**
   * The plugin ID.
   */
  public string $id;

  /**
   * The plugin label.
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

  /**
   * A brief, human-readable, description of the fallback type.
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

}
