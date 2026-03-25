<?php

namespace Drupal\wateraid_core\Plugin\TfaSetup;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tfa\Plugin\TfaSetup\TfaTotpSetup;
use Drupal\tfa\Plugin\TfaSetupInterface;

/**
 * WaterAid TOTP setup class to set up TOTP validation.
 *
 * @TfaSetup(
 *   id = "wa_tfa_totp_setup",
 *   label = @Translation("WaterAid TFA TOTP Setup"),
 *   description = @Translation("WaterAid TFA TOTP Setup Plugin"),
 *   helpLinks = {
 *    "Okta Verify (Android)" = "https://play.google.com/store/apps/details?id=com.okta.android.auth",
 *    "Okta Verify (iOS)" = "https://apps.apple.com/us/app/okta-verify/id490179405",
 *    "Google Authenticator (Android/iOS)" = "https://googleauthenticator.net",
 *    "Microsoft Authenticator (Android/iOS)" = "https://www.microsoft.com/en-us/security/mobile-authenticator-app",
 *    "Authy (Android/iOS/Desktop)" = "https://authy.com",
 *    "FreeOTP (Android/iOS)" = "https://freeotp.github.io",
 *    "GAuth Authenticator (Desktop)" = "https://github.com/gbraadnl/gauth"
 *   },
 *   setupMessages = {
 *    "saved" = @Translation("Application code verified."),
 *    "skipped" = @Translation("Application codes not enabled.")
 *   }
 * )
 */
class WaterAidTfaTotpSetup extends TfaTotpSetup implements TfaSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function getSetupForm(array $form, FormStateInterface $form_state): array {
    $form = parent::getSetupForm($form, $form_state);

    $form['apps']['#title'] = $this->t('Install a code generator app on your device');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getOverview(array $params): array {
    return [
      'heading' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Two-factor authentication'),
      ],
      'validation_plugin' => [
        '#type' => 'markup',
      ],
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Generate verification codes from a mobile or desktop application.'),
      ],
      'link' => [
        '#theme' => 'links',
        '#links' => [
          'admin' => [
            'title' => !$params['enabled'] ? $this->t('Set up application') : $this->t('Reset application'),
            'url' => Url::fromRoute('tfa.validation.setup', [
              'user' => $params['account']->id(),
              'method' => $params['plugin_id'],
            ]),
          ],
        ],
      ],
      '#weight' => 0,
    ];
  }

}
