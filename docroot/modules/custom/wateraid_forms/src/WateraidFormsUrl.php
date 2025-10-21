<?php

namespace Drupal\wateraid_forms;

/**
 * Class WateraidFormsServiceProvider.
 *
 * @package Drupal\wateraid_forms
 */
abstract class WateraidFormsUrl {

  /**
   * Some default URL parameters.
   */
  public const WATERAID_URL_PARAMETERS = [
    'utm_campaign',
    'utm_source',
    'utm_content',
    'utm_medium',
    'id',
  ];

}
