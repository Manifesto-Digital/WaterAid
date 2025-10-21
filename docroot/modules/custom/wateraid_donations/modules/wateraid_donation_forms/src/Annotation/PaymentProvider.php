<?php

namespace Drupal\wateraid_donation_forms\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a donation webforms payment provider annotation object.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class PaymentProvider extends Plugin {

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
   * A brief, human-readable, description of the payment provider.
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

  /**
   * Type of payment supported by the payment provider.
   */
  public string $type;

  /**
   * Javascript view for client-side integration.
   */
  public string $jsView;

  /**
   * Payment type id of payment type plugin.
   */
  public string $paymentType;

  /**
   * Enable the payment provider by default when adding new handlers.
   */
  public bool $enableByDefault = FALSE;

}
