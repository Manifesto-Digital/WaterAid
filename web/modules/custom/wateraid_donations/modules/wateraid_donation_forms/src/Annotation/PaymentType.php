<?php

namespace Drupal\wateraid_donation_forms\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a donation webforms payment type annotation object.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class PaymentType extends Plugin {

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
   * A brief, human-readable, description of the payment type.
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

  /**
   * The weight of the plugin.
   */
  public int $weight;

  /**
   * Prefix used to identify payment type in exports.
   */
  public string $prefix;

}
