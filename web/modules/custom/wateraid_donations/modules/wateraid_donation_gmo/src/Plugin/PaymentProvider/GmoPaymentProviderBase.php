<?php

namespace Drupal\wateraid_donation_gmo\Plugin\PaymentProvider;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wateraid_donation_forms\Exception\PaymentException;
use Drupal\wateraid_donation_forms\Exception\UserFacingPaymentException;
use Drupal\wateraid_donation_forms\PaymentProviderBase;
use Drupal\wateraid_donation_gmo\WaterAidWebformGmoService;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the GmoPaymentProviderBase class.
 *
 * @package Drupal\wateraid_donation_gmo\Plugin\PaymentProvider
 */
abstract class GmoPaymentProviderBase extends PaymentProviderBase {

  /**
   * The WaterAidWebformGmoService.
   */
  protected WaterAidWebformGmoService $webformGmoService;

  /**
   * A logger instance.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WaterAidWebformGmoService $webform_gmo_service, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->webformGmoService = $webform_gmo_service;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('wateraid_webform_gmo'),
      $container->get('logger.channel.wateraid_donation_gmo'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processWebformComposite(array &$element, FormStateInterface $form_state, array &$complete_form): void {

    if ($this->getPaymentType() == 'card') {
      $element['card_message'] = [
        '#markup' => '<p>' . $this->t('We accept credit cards with the following marks.') . '</p>',
      ];

      $element['cards_accepted'] = [
        '#markup' => '<p class="donation-inline-logos"><span class="visually-hidden">Visa, Mastercard, Amex, JCB, Diners Club International.</span></p>',
      ];

      $element['cardholder'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Card holders name'),
        '#attributes' => [
          'id' => Html::getUniqueId('gmo-cardholder-name-' . $this->getPaymentFrequency()),
          'frequency' => $this->getPaymentFrequency(),
          'method' => $this->getPluginId(),
          'class' => [
            'clear-on-submit',
          ],
        ],
      ];

      $element['card_number'] = [
        '#type' => 'number',
        '#title' => $this->t('Card number'),
        '#attributes' => [
          'id' => Html::getUniqueId('gmo-card-' . $this->getPaymentFrequency()),
          'frequency' => $this->getPaymentFrequency(),
          'method' => $this->getPluginId(),
          'data-rule-minlength' => 10,
          'data-rule-maxlength' => 16,
          'data-validate-max-length' => 16,
          'class' => [
            'clear-on-submit',
          ],
        ],
      ];

      $element['expiry_csv'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'inline-desktop-fields',
            'inline-desktop-fields--60-40',
          ],
        ],
      ];

      $element['expiry_csv']['expiration'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Expiry date'),
        '#attributes' => [
          'class' => [
            'fieldset-without-border',
            'fieldset-inline-elements',
            'fieldset-show-legend',
            'fieldset-hidden-field-labels',
          ],
        ],
        'month' => [
          '#type' => 'number',
          '#title' => $this->t('Expiry date month'),
          '#suffix' => '<span class="inline-seperator">/</span>',
          '#min' => 1,
          '#max' => 12,
          '#step' => 1,
          '#attributes' => [
            'id' => Html::getUniqueId('gmo-expiration-month-' . $this->getPaymentFrequency()),
            'placeholder' => 'MM',
            'frequency' => $this->getPaymentFrequency(),
            'method' => $this->getPluginId(),
            'data-validate-max-length' => 2,
            'class' => [
              'clear-on-submit',
            ],
          ],
        ],
        'year' => [
          '#type' => 'number',
          '#title' => $this->t('Expiry date Year'),
          '#min' => date('Y'),
          '#max' => (int) date('Y') + 20,
          '#step' => 1,
          '#attributes' => [
            'id' => Html::getUniqueId('gmo-expiration-year-' . $this->getPaymentFrequency()),
            'placeholder' => 'YYYY',
            'frequency' => $this->getPaymentFrequency(),
            'method' => $this->getPluginId(),
            'data-validate-max-length' => 4,
            'class' => [
              'clear-on-submit',
            ],
          ],
        ],
      ];

      $element['expiry_csv']['security_code'] = [
        '#type' => 'number',
        '#title' => $this->t('Security code'),
        '#attributes' => [
          'id' => Html::getUniqueId('gmo-security-code-' . $this->getPaymentFrequency()),
          'frequency' => $this->getPaymentFrequency(),
          'method' => $this->getPluginId(),
          'data-rule-minlength' => 3,
          'data-rule-maxlength' => 4,
          'data-validate-max-length' => 4,
          'class' => [
            'clear-on-submit',
          ],
        ],
      ];

      $element['payment_token'] = [
        '#type' => 'hidden',
        '#attributes' => [
          'id' => Html::getUniqueId('gmo-payment-token-' . $this->getPaymentFrequency()),
        ],
      ];
    }

    // Set the Shop ID for use in the front-end.
    $element['#attached']['drupalSettings']['webformGmo']['shop_id'] = $this->webformGmoService->getShopId();
  }

  /**
   * {@inheritdoc}
   */
  public function processPayment(array $payment, ?WebformInterface $webform = NULL): bool {
    if ($this->getPaymentType() == 'card' && empty($payment['payment_details']['payment_token'])) {
      // To process a card payment, a payment token must be present.
      throw new PaymentException('Missing payment token');
    }
    else {
      $payment_token = $payment['payment_details']['payment_token'];
    }

    if (empty($payment['webform_submission']) || !($payment['webform_submission'] instanceof WebformSubmissionInterface)) {
      throw new PaymentException('WebForm submission data is missing');
    }

    $webform_submission_data = $payment['webform_submission']->getData();

    $data = [
      'FirstNm' => $webform_submission_data['first_name'] ?? '',
      'LastNm' => $webform_submission_data['last_name'] ?? '',
      'FirstKnNm' => $webform_submission_data['first_name_in_japanese'] ?? '',
      'LastKnNm' => $webform_submission_data['last_name_in_japanese'] ?? '',
      'PostCd' => $webform_submission_data['postcode'] ?? '',
      'State' => $webform_submission_data['prefecture'] ?? '',
      'City' => $webform_submission_data['city'] ?? '',
      'Street' => $webform_submission_data['street'] ?? '',
      'Phone' => $webform_submission_data['phone'] ?? '',
      'Fax' => $webform_submission_data['fax'] ?? '',
      'Email' => $webform_submission_data['email']['email'] ?? '',
      'EmailMaga' => $webform_submission_data['e_newsletter'] ?? '0',
      'Receipt' => $webform_submission_data['receipt'] == 'yes' ? '1' : '0',
      'Memo' => $webform_submission_data['memo'] ?? '',
      'Company' => $webform_submission_data['corporate_name'] ?? '',
      'CompanyKnNm' => $webform_submission_data['corporate_name_in_japanese'] ?? '',
      'Amount' => $payment['amount'] ?? '',
    ];

    $data['Token'] = $this->webformGmoService->getSalesforceToken();
    $data['SuccessURL'] = 'https://www.google.co.jp';
    $data['FailURL'] = 'https://yahoo.co.jp';

    // Submission language is Japanese.
    $data['Language'] = '日本語';

    // Account type (1 = Individual, 2 = Corporate).
    $account_types = [
      'individual' => '1',
      'corporate' => '2',
    ];
    $data['AccTyp'] = $account_types[$webform_submission_data['individual_corporate']] ?? '';

    // GMO MultiPayment token.
    $data['PtToken'] = $payment_token ?? '';

    /*
     * Payment frequency:
     *   1 = Donation (once)
     *   2 = Donation (monthly)
     *   3 = Donation (once a year)
     */
    $payment_frequencies = [
      'one_off' => '1',
      'recurring' => '2',
    ];
    $data['PtTyp'] = $payment_frequencies[$webform_submission_data['payment']['payment_frequency']] ?? '';

    // Payment method (1 = Credit card, 2 = Bank transfer).
    $payment_types = [
      'card' => '1',
      'bank_transfer' => '2',
    ];

    $data['PtWay'] = $payment_types[$this->getPaymentType()] ?? '';

    $client = new Client();
    try {
      $uri = $this->webformGmoService->getSalesforceUrl();
      $response = $client->post($uri, [
        RequestOptions::FORM_PARAMS => $data,
      ]);

      // Parse the response body to extract error messages.
      $body = $response->getBody()->getContents();
      $doc = new \DOMDocument();
      $doc->loadHTML($body);
      $error_html = '';
      if ($doc->getElementsByTagName('ul')->count() > 0) {
        $error_html = $doc->getElementsByTagName('ul')->item(0)->textContent;
      }

      if ($response->getStatusCode() == '200') {
        if (empty($error_html)) {
          return TRUE;
        }
        else {
          // Pass error messages back to the front-end.
          throw new UserFacingPaymentException($error_html);
        }
      }
      else {
        // Unexpected response.
        throw new PaymentException('The API returned a non-200 response');
      }
    }
    catch (RequestException $e) {
      // General exception error.
      throw new PaymentException('An error occurred during payment');
    }
  }

}
