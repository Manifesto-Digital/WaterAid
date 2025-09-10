<?php

namespace Drupal\loqate\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Config form for Loqate module.
 */
class LoqateApiKeyConfigForm extends ConfigFormBase {

  /**
   * Config key for the default API key.
   */
  public const DEFAULT_API_KEY = 'loqate_api_key';

  /**
   * The Module Handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ConfigFormBase {
    $instance = parent::create($container);
    $instance->moduleHandler = $container->get('module_handler');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'loqate.loqateapikeyconfig',
      'webform_capture_plus.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'loqate_api_key_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('loqate.loqateapikeyconfig');

    $read_more_url = Url::fromUri('https://www.loqate.com/resources/support/setup-guides/advanced-setup-guide/#creating_a_key');
    $description_read_more_link = Link::fromTextAndUrl('Read more about the Loqate API.', $read_more_url)->toString();

    $form['information'] = [
      '#markup' => '<p>' . $description_read_more_link . '</p>',
    ];

    $form['test_api_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Loqate API Key (test)'),
      '#default_value' => $config->get('test_api_key'),
    ];

    $form['live_api_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Loqate API Key (live)'),
      '#default_value' => $config->get('live_api_key'),
    ];
    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mode'),
      '#options' => [
        'test' => $this->t('Test'),
        'live' => $this->t('Live'),
      ],
      '#default_value' => $config->get('mode'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config('loqate.loqateapikeyconfig')
      ->set('mode', $form_state->getValue('mode'))
      ->set('test_api_key', $form_state->getValue('test_api_key'))
      ->set('live_api_key', $form_state->getValue('live_api_key'))
      ->save();

    // If the Webform Capture Plus module exists, keep the two in sync.
    if ($this->moduleHandler->moduleExists('webform_capture_plus')) {
      $this->config('webform_capture_plus.settings')
        ->set('mode', $form_state->getValue('mode'))
        ->set('test_api_key', $form_state->getValue('test_api_key'))
        ->set('live_api_key', $form_state->getValue('live_api_key'))
        ->save();
    }
  }

}
