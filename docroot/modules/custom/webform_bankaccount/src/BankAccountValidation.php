<?php

namespace Drupal\webform_bankaccount;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use GuzzleHttp\Client;

/**
 * Bank Account Validation.
 */
class BankAccountValidation {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * GuzzleHttp\Client definition.
   */
  protected Client $httpClient;

  /**
   * Constructs a new BankAccountValidation object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Client $http_client) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
  }

  /**
   * Call back function to validate the bank account with PCA API.
   *
   * @param mixed[] $payment_details
   *   Payment details array.
   *
   * @return bool|null
   *   Validation result.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function validateBankAccount(array $payment_details): bool|null {
    $loqate_bank_api_settings = $this->configFactory->get('webform_bankaccount.loqateapisettings');
    $key = $loqate_bank_api_settings->get('api_key');
    $url = Url::fromUri($loqate_bank_api_settings->get('bank_verification_api_url'));
    $account_number = $payment_details['payment_details']['bank_account']['account'];
    $sort_code = $payment_details['payment_details']['bank_account']['sort_code'];
    try {
      $response = $this->httpClient->get($url->getUri(), [
        'query' => [
          '_format' => 'json',
          'callback' => '',
          'Key' => $key,
          'AccountNumber' => $account_number,
          'SortCode' => $sort_code,
        ],
      ]);
      $data = Json::decode($response->getBody());
      if ($data['Items'][0]['IsCorrect'] === TRUE && $data['Items'][0]['IsDirectDebitCapable'] === TRUE) {
        return TRUE;
      }
      else {
        return NULL;
      }
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

}
