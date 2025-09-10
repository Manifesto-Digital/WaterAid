<?php

namespace Drupal\loqate\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Updates Loqate config if Capture Plus config exists and changes.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * The configuration factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a new class object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Ensures Loqate config is kept in sync with Capture Plus config changes.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    $capture_plus_config = $event->getConfig();
    if ($capture_plus_config->getName() !== 'webform_capture_plus.settings') {
      return;
    }
    $loqate_config = $this->configFactory->getEditable('loqate.loqateapikeyconfig');

    $loqate_config->set('mode', $capture_plus_config->get('mode'))
      ->set('test_api_key', $capture_plus_config->get('test_api_key'))
      ->set('live_api_key', $capture_plus_config->get('live_api_key'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 0];
    return $events;
  }

}
