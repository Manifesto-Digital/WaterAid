<?php

declare(strict_types=1);

namespace Drupal\wa_orange_dam\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Orange DAM form.
 */
final class Oauth2Form extends FormBase {

  /**
   * Constructs the form.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The State service.
   */
  public function __construct(
    private readonly StateInterface $state,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wa_orange_dam_oauth2';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['intro'] = [
      '#markup' => t('<p>This form will redirect you to authorise API access to the Orange DAM.</p><p>You do not need to submit the form if you have not received an error message that you need to revalidate.</p>'),
    ];
    $form['oauth2-checkbox'] = [
      '#type' => 'checkbox',
      '#default_value' => $form_state->getValue('default_value') ?? 0,
      '#title' => t('Use custom Oauth2 client and secret'),
      '#description' => t('If selected, you will need to enter CLIENT SECRET and CLIENT ID tokens. Otherwise the default will be used.'),
      '#attributes' => [
        'name' => 'oauth2-checkbox',
      ],
    ];
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => t('Orange DAM Client ID'),
      '#states' => [
        'visible' => [
          ':input[name="oauth2-checkbox"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => t('Orange DAM Client Secret'),
      '#states' => [
        'visible' => [
          ':input[name="oauth2-checkbox"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Authorise'),
      ],
    ];

    /** @var \Drupal\wa_orange_dam\Service\Api $service */
    $service = \Drupal::service('wa_orange_dam.api');
    $one = $service->authorize();

    if ($one['access_token']) {
      $service->search([], $one['access_token']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if ($form_state->getValue('oauth2-checkbox')) {
      foreach ([
        'client_id' => t('Client ID'),
        'client_secret' => t('Client Secret'),
      ] as $field => $label) {
        if (!$form_state->getValue($field)) {
          $form_state->setError($form[$field], $this->t('You must set a :field if you are overriding the default values.', [
            ':field' => $label,
          ]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($form_state->getValue('oauth2-checkbox')) {
      $client_id = $form_state->getValue('client_id');
      $client_secret = $form_state->getValue('client_secret');
    }
    else {
      $client_id = Settings::get('orange_dam_id');
      $client_secret = Settings::get('orange_dam_secret');
    }

    if (!$client_id || !$client_secret) {
      $this->messenger()->addError($this->t('Default Client ID and/or Client Secret have not been set.'));
      return;
    }

    $redirect = Url::fromRoute('wa_orange_dam.oauth2redirect');
    $redirect->setAbsolute();
    $redirect_url = $redirect->toString();

    // Generate and store a random token to validate the return URL.
    $token = Html::escape(Crypt::randomBytesBase64());

    $this->state->set('wa_orange_dam_authorize_token', $token);

    $url = "https://dam.wi0.orangelogic.com/oauth2/auth?&client_id=$client_id&response_type=code&redirect_uri=$redirect_url&state=$token";

    $return = new TrustedRedirectResponse($url);

    $form_state->setResponse($return);
  }

}
