<?php

namespace Drupal\wateraid_webform_encrypt\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\encrypt\EncryptionProfileManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform Encrytion settings form.
 *
 * @package Drupal\wateraid_webform_encrypt\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The encryption objects, as provided by the EncryptionProfileManager.
   *
   * @var mixed[]
   *   the encryption options.
   */
  protected array $encryptionOptions;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\encrypt\EncryptionProfileManager $encryptionManager
   *   The encryption manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EncryptionProfileManager $encryptionManager) {
    parent::__construct($config_factory);
    $this->encryptionOptions = $encryptionManager->getEncryptionProfileNamesAsOptions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('encrypt.encryption_profile.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wateraid_webform_encrypt_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['wateraid_webform_encrypt.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('wateraid_webform_encrypt.settings');

    $form['encryption_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Encryption profile'),
      '#options' => $this->encryptionOptions,
      '#default_value' => $config->get('encryption_profile'),
    ];

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#description' => $this->t('Note by enabling encryption all webforms from this point will be encrypted.  This will also make previously submitted webforms unreadble.'),
      '#default_value' => $config->get('enabled'),
    ];

    if (empty($this->encryptionOptions)) {
      $form['encryption_profile']['#disabled'] = TRUE;
      $form['encryption_profile']['#description'] = $this->t('Please create an encryption profile to use first.');

      $form['enabled']['#disabled'] = TRUE;
      $form['enabled']['#default_value'] = 0;
    }

    $link = Link::createFromRoute('click here', 'wateraid_webform_encrypt.batch_job');
    $form['encrypt_batch'] = [
      '#type' => 'markup',
      '#weight' => 9999,
      '#markup' => $this->t('To encrypt all existing submissions on the site in a batch job (note this may take a while), @click_here.', ['@click_here' => $link->toString()]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    if ($form_state->getValue('enabled') && empty($form_state->getValue('encryption_profile'))) {
      $form_state->setError($form['encryption_profile'], $this->t('You must select an encryption profile to use in order to enable encryption'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('wateraid_webform_encrypt.settings');
    if (empty($config->get('enabled')) && $form_state->getValue('enabled')) {
      $this->messenger()->addWarning($this->t('You have now enabled encryption.  All webform submissions will now be encrypted.'));
    }
    elseif ($config->get('enabled') && empty($form_state->getValue('enabled'))) {
      $this->messenger()->addWarning($this->t('You have disabled encryption.  Please note that any previously (encrypted) webform submissions will no longer be viewable.'));
    }

    $config
      ->set('encryption_profile', $form_state->getValue('encryption_profile'))
      ->set('enabled', $form_state->getValue('enabled'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
