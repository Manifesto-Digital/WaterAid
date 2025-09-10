<?php

namespace Drupal\wateraid_base_core\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\config_pages\ConfigPagesLoaderService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Donation Menu Header' condition.
 *
 * @todo Why is this class an exact replica of DonationMenuFooter w/o a base class?
 *
 * @Condition(
 *   id = "donation_menu_header",
 *   label = @Translation("Donation Menu Header"),
 *   context_definitions = {
 *     "block" = @ContextDefinition("entity:block", label = @Translation("Block"), required = FALSE),
 *   }
 * )
 */
class DonationMenuHeader extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The config page loader service.
   */
  protected ConfigPagesLoaderService $configPagesLoader;

  /**
   * Donation page constructor.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\config_pages\ConfigPagesLoaderService $config_pages_loader
   *   The config page loader service..
   */
  public function __construct(array $configuration, string $plugin_id, mixed $plugin_definition, ConfigPagesLoaderService $config_pages_loader) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configPagesLoader = $config_pages_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config_pages.loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $donation_page_menus_visibility = $this->configPagesLoader->getValue('donation_page_menus', 'field_donation_menu_visibility');
    if (isset($donation_page_menus_visibility[0]['value'])) {
      if ($donation_page_menus_visibility[0]['value'] === 'header' || $donation_page_menus_visibility[0]['value'] === 'both') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // @todo Implement summary() method.
  }

}
