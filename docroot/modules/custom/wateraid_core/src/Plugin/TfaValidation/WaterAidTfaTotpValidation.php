<?php

namespace Drupal\wateraid_core\Plugin\TfaValidation;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\tfa\Plugin\TfaValidation\TfaTotpValidation;
use Drupal\tfa\Plugin\TfaValidationInterface;
use Drupal\user\UserDataInterface;
use Otp\GoogleAuthenticator;
use Otp\Otp;

/**
 * WaterAid TOTP validation class for performing TOTP validation.
 *
 * @TfaValidation(
 *   id = "wa_tfa_totp",
 *   label = @Translation("WaterAid TFA Time-based one-time password (TOTP)"),
 *   description = @Translation("WaterAid TFA TOTP Validation Plugin"),
 *   setupPluginId = "wa_tfa_totp_setup",
 * )
 */
class WaterAidTfaTotpValidation extends TfaTotpValidation implements TfaValidationInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptServiceInterface $encrypt_service, ConfigFactoryInterface $config_factory, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $user_data, $encryption_profile_manager, $encrypt_service, $config_factory, $time);
    $this->auth = new \StdClass();
    $this->auth->otp = new Otp();
    $this->auth->ga = new GoogleAuthenticator();
    // Allow codes within tolerance range of 2 * 30 second units.
    $plugin_settings = $config_factory->get('tfa.settings')->get('validation_plugin_settings');
    $settings = $plugin_settings['wa_tfa_totp'] ?? [];
    $settings = array_replace([
      'time_skew' => 2,
      'site_name_prefix' => TRUE,
      'name_prefix' => 'TFA',
      'issuer' => 'Drupal',
    ], $settings);
    $this->timeSkew = $settings['time_skew'];
    $this->siteNamePrefix = $settings['site_name_prefix'];
    $this->namePrefix = $settings['name_prefix'];
    $this->issuer = $settings['issuer'];
    $this->alreadyAccepted = FALSE;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(Config $config, array $state = []): array {
    $settings_form = parent::buildConfigurationForm($config, $state);

    // Replace reference to "tfa_totp" with the new plugin ID.
    unset($state['visible'][':input[name="validation_plugin_settings[tfa_totp][site_name_prefix]"]']);
    $state['visible'] += [
      ':input[name="validation_plugin_settings[wa_tfa_totp][site_name_prefix]"]' => ['checked' => FALSE],
    ];
    $settings_form['name_prefix']['#states'] = $state;

    return $settings_form;
  }

}
