<?php

namespace Drupal\loqate_email;

use Baikho\Loqate\Email\Validate;
use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\key\KeyRepositoryInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * The validator service.
 */
class ValidatorService implements ValidatorInterface {

  use StringTranslationTrait;

  /**
   * Drupal\key\KeyRepositoryInterface definition.
   */
  protected KeyRepositoryInterface $keyRepository;

  /**
   * Drupal Core email validator.
   */
  protected EmailValidatorInterface $emailValidator;

  /**
   * Loqate configuration settings.
   */
  protected ImmutableConfig $config;

  /**
   * Logging channel.
   */
  protected LoggerChannelInterface $logger;

  /**
   * Constructs a new ValidatorService object.
   */
  public function __construct(
    KeyRepositoryInterface $key_repository,
    EmailValidatorInterface $email_validator,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->keyRepository = $key_repository;
    $this->emailValidator = $email_validator;
    $this->config = $config_factory->get('loqate_email.settings');
    $this->logger = $logger_factory->get('loqate_email');
  }

  /**
   * {@inheritdoc}
   */
  public function validateEmailAddress(string $email, bool $refuse_disposable_temporary_emails = TRUE): array {
    // Initialise default return values.
    $return = [
      'skipped' => FALSE,
      'skipped_message' => FALSE,
      'valid' => FALSE,
      'invalid_email_error_message' => $this->getErrorMessage(),
      'hash' => '',
    ];

    $debug = (bool) $this->config->get('debug');

    // Validate email address format with Drupal.
    $valid_format = $this->emailValidator->isValid($email);

    if (!$valid_format) {
      // Skip any further checks until this passes.
      if ($debug) {
        $this->logger->debug($this->t('The email format is incorrect - API call skipped'));
      }
      return $return;
    }

    // Short circuit if globally disabled.
    if ($this->config->get('disable_globally') == 1) {
      $return['skipped'] = TRUE;
      if ($debug) {
        $this->logger->debug($this->t('The "Disable globally" option is enabled - API call skipped'));
      }
      return $return;
    }

    // Perform Loqate API call when live mode is enabled.
    if ($this->config->get('mode') == 'live') {
      $api_key = $this->getApiKey();

      if (empty($api_key)) {
        $link = Link::createFromRoute('Configure', 'loqate_email.settings_form')->toString();
        $this->logger->warning($this->t('Loqate email API key is missing or empty. @link', ['@link' => $link]));
        $return['valid'] = FALSE;
        return $return;
      }
      else {
        $api_response = $this->requestApiCall($api_key, $email);
      }

      if (!empty($api_response)) {
        // Parse the API response into settings for return.
        if (!array_key_exists('ResponseCode', $api_response)) {
          // Log unexpected response and trigger validation failure.
          $error_markup = print_r($api_response, TRUE);
          $this->logger->warning($this->t('Loqate API returned an unexpected response: @error',
            ['@error' => $error_markup]
          ));
          $return['valid'] = FALSE;
          return $return;
        }

        switch ($api_response['ResponseCode']) {
          case "Valid":
            // The email address has been fully validated
            // (including the account portion).
            $valid = TRUE;
            break;

          case "Valid_CatchAll":
            // The domain has been validated but the account
            // could not be validated. We have to assume
            // this is valid otherwise all temporary mail
            // providers will be rejected (because they
            // generate accounts on-the-fly). If the option
            // to refuse temp/disposable emails has been
            // enabled, this will override $valid below.
            $valid = TRUE;
            break;

          default:
            $valid = FALSE;
            break;
        }

        if ($refuse_disposable_temporary_emails && $api_response['IsDisposableOrTemporary'] === TRUE) {
          // Flag invalid email if asked us to refuse disposable emails.
          $return['valid'] = FALSE;
        }
        else {
          $return['valid'] = $valid;
        }

        if ($debug) {
          $this->logger->debug($this->t('API call executed - @call', ['@call' => print_r($api_response, TRUE)]));
        }
      }
      else {
        // Log empty response and trigger validation failure.
        $this->logger->warning($this->t('Loqate API did not return a response.'));
        $return['valid'] = FALSE;
        return $return;
      }
    }
    else {
      // API calls are skipped unless live mode is enabled.
      $return['skipped'] = TRUE;
      $return['skipped_message'] = $this->t('Loqate email validation skipped - sandbox mode is not available on this endpoint');
      if ($debug) {
        $this->logger->debug($this->t('Live mode is not enabled - API call skipped'));
      }
    }

    // Add a hash if the email is valid and an API call was made.
    if ($return['valid'] === TRUE && $return['skipped'] === FALSE) {
      $return['hash'] = $this->getHash($email);
    }

    return $return;
  }

  /**
   * Load API key based on the mode.
   *
   * @return string|null
   *   If configured, returns the value of the API key for the current mode.
   */
  private function getApiKey(): ? string {
    $settings = $this->config;
    $api_key = NULL;

    if ($settings->get('mode') == 'live') {
      // Get the API key.
      $active_key = 'live_api_key';

      // Load the key config for the current mode.
      $key_id = $settings->get($active_key);

      // Load the API key value.
      $api_key = $this->keyRepository->getKey($key_id)->getKeyValue();
    }

    return $api_key;
  }

  /**
   * {@inheritdoc}
   */
  public function getApiTimeoutMs(): int {
    // Get timeout from config.
    $timeout = $this->config->get('timeout');
    if (empty($timeout) || !is_numeric($timeout) || $timeout < 1 || $timeout > 15000) {
      // Default to 15000.
      return 15000;
    }

    return $timeout;
  }

  /**
   * Execute a GuzzleHttp request to the Loqate Validate API.
   *
   * @param string $api_key
   *   The Loqate API key to use.
   * @param string $email
   *   Email address to validate.
   *
   * @return mixed[]|null
   *   Array of response data.
   */
  private function requestApiCall(string $api_key, string $email): ? array {
    try {
      $timeout = $this->getApiTimeoutMs();

      // Construct Validate request using the values provided.
      $request = (new Validate($api_key, $email, $timeout));

      // Call the API.
      $response = $request->makeRequest();
      if ($response) {
        // Type Cast the response data into an array.
        return (array) $response->Items[0];
      }
    }
    catch (GuzzleException $e) {
      $this->logger->error($e->getMessage());
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage(): ? string {
    return $this->config->get('invalid_email_error_message') ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getHash($email): string {
    // Make the hash stronger with a salt.
    $salt = "SJ6J43GS73HDY67E";
    return md5($email . $salt);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): ?array {
    return $this->config->getCacheTags();
  }

}
