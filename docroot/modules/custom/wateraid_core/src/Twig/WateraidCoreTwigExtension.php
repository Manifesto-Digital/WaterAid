<?php

namespace Drupal\wateraid_core\Twig;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Define custom twig extensions for WaterAid features.
 */
class WateraidCoreTwigExtension extends AbstractExtension {
  use StringTranslationTrait;

  /**
   * Constructs a WateraidCoreTwigExtension instance.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   Theme manager service.
   */
  public function __construct(
    protected ThemeManagerInterface $themeManager,
    protected MessengerInterface $messenger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('get_spritemap', [$this, 'getSpritemap']),
    ];
  }

  /**
   * Get the path to the theme's spritemap.
   */
  public function getSpritemap(): string {
    // Make the spritemap available as to all twig templates.
    $theme_path = $this->themeManager->getActiveTheme()->getPath();
    $sprite_path = $theme_path . '/dist/images/sprite.svg';
    $full_path = DRUPAL_ROOT . '/' . $sprite_path;

    // Check if file exists.
    if (!file_exists($full_path)) {
      // Use a static variable to only show the message once per request.
      static $svg_spritemap_warning_shown = FALSE;

      if (!$svg_spritemap_warning_shown) {
        $this->messenger->addWarning(
          $this->t('Theme assets need to be compiled to provide an SVG spritemap. Please run `make build-frontend` from the theme root to build assets for the @theme theme.', [
            '@theme' => $this->themeManager->getActiveTheme()->getName(),
          ])
        );
        $svg_spritemap_warning_shown = TRUE;
      }

      // Return empty string or a fallback path.
      return '';
    }

    return '/' . $sprite_path;
  }

}
