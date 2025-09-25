<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Display mode buttons Trait.
 *
 * @package Drupal\wateraid_donation_forms
 */
trait DisplayModeButtonsTrait {

  use StringTranslationTrait;

  /**
   * Get display mode button class names for an element.
   *
   * @param mixed[] $element
   *   The webform element to check.
   * @param string $key
   *   Key within the element indicating the display mode.
   *
   * @return string
   *   The clean class name.
   */
  public static function getDisplayModeClassByElement(array $element, string $key = '#display_mode'): string {
    if (isset($element[$key])) {
      return Html::getClass('display-mode-' . $element[$key]);
    }
    else {
      // Default value.
      $default = self::getDefaultDisplayMode();
      return Html::getClass('display-mode-' . $default);
    }
  }

  /**
   * Get the list of available button display modes.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   Array of options.
   */
  public static function getDisplayModeOptions(): array {
    return [
      'large' => t('Large radio buttons (default)'),
      'minimal' => t('Minimal radio buttons'),
    ];
  }

  /**
   * Provides the default option.
   *
   * @return string
   *   The default option.
   */
  public static function getDefaultDisplayMode(): string {
    return 'large';
  }

}
