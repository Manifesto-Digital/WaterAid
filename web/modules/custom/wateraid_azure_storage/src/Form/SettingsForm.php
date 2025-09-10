<?php

namespace Drupal\wateraid_azure_storage\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for Azure Storage.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Queue interface.
   */
  protected QueueInterface $azureStorageQueue;

  /**
   * Queue interface.
   */
  protected QueueInterface $azureStorageErrorNotifyQueue;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, QueueFactory $queue_factory) {
    parent::__construct($config_factory);
    $this->azureStorageQueue = $queue_factory->get('wateraid_azure_storage_queue');
    $this->azureStorageErrorNotifyQueue = $queue_factory->get('wateraid_azure_storage_error_notify_queue');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'wateraid_azure_storage.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wateraid_azure_storage_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('wateraid_azure_storage.settings');

    $form['retry_messages'] = [
      '#type' => 'details',
      '#title' => $this->t('Internal Retry Queue'),
      '#open' => TRUE,
    ];

    $form['retry_messages']['markup'] = [
      '#markup' => $this->t('Items in the internal retry queue: @count', [
        '@count' => $this->azureStorageQueue->numberOfItems(),
      ]),
    ];

    $form['notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Notifications'),
      '#open' => TRUE,
    ];

    $form['notifications']['error_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Notifications'),
      '#default_value' => $config->get('error_notify'),
    ];

    $form['notifications']['error_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Error Mailbox'),
      '#description' => $this->t('The recipient(s) for Azure Storage Queue error notifications. Please use comma separated values.'),
      '#default_value' => $config->get('error_mail'),
    ];

    $form['notifications']['markup'] = [
      '#markup' => $this->t('Items in the error notification queue: @count', [
        '@count' => $this->azureStorageErrorNotifyQueue->numberOfItems(),
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config('wateraid_azure_storage.settings')
      ->set('error_notify', (bool) $form_state->getValue('error_notify'))
      ->set('error_mail', $form_state->getValue('error_mail'))
      ->save();
  }

}
