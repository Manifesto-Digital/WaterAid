<?php

namespace Drupal\wateraid_donation_forms\Plugin\WebformHandler;

use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Exception\UnknownCountryException;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\currency\Entity\Currency;
use Drupal\currency\Entity\CurrencyInterface;
use Drupal\currency\FormHelperInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\wateraid_donation_forms\DonationConstants;
use Drupal\wateraid_donation_forms\DonationService;
use Drupal\wateraid_donation_forms\DonationServiceInterface;
use Drupal\wateraid_donation_forms\Element\DonationsWebformAmount;
use Drupal\wateraid_donation_forms\Element\DonationsWebformPayment;
use Drupal\wateraid_donation_forms\Exception\PaymentException;
use Drupal\wateraid_donation_forms\Exception\UserFacingPaymentException;
use Drupal\webform\Form\WebformHandlerAddForm;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Donations Webform Handler.
 *
 * @package Drupal\wateraid_donation_forms\Plugin\WebformHandler
 *
 * @WebformHandler(
 *   id = "wateraid_donations",
 *   label = @Translation("WaterAid Donations"),
 *   category = @Translation("WaterAid Donations"),
 *   description = @Translation("Processes donation payments."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class DonationsWebformHandler extends WebformHandlerBase {

  /**
   * The form helper.
   */
  protected FormHelperInterface $formHelper;

  /**
   * The date formatter service.
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * The Donation service.
   */
  protected DonationServiceInterface $donationService;

  /**
   * The request stack.
   */
  protected Request $request;

  /**
   * The country repository.
   */
  protected CountryRepositoryInterface $countryRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setCurrencyFormHelper($container->get('currency.form_helper'));
    $instance->setDonationService($container->get('wateraid_donation_forms.donation'));
    $instance->setDateFormatter($container->get('date.formatter'));
    $instance->setRequestStack($container->get('request_stack'));
    $instance->countryRepository = new CountryRepository();
    return $instance;
  }

  /**
   * Sets the Currency Form Helper service.
   *
   * @param \Drupal\currency\FormHelperInterface $form_helper
   *   Form helper class.
   *
   * @return $this
   */
  protected function setCurrencyFormHelper(FormHelperInterface $form_helper): static {
    $this->formHelper = $form_helper;
    return $this;
  }

  /**
   * Sets the Donation service.
   *
   * @param \Drupal\wateraid_donation_forms\DonationServiceInterface $donation_service
   *   Donation service class.
   *
   * @return $this
   */
  protected function setDonationService(DonationServiceInterface $donation_service): static {
    $this->donationService = $donation_service;
    return $this;
  }

  /**
   * Sets the date formatter.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  protected function setDateFormatter(DateFormatterInterface $date_formatter): void {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Sets the current request.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The new request stack.
   *
   * @return $this
   */
  public function setRequestStack(RequestStack $request_stack): static {
    $this->request = $request_stack->getCurrentRequest();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['default_fund_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default fund code'),
      '#default_value' => $this->configuration['default_fund_code'],
    ];

    $form['default_package_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default package code'),
      '#default_value' => $this->configuration['default_package_code'],
    ];

    $form['cancellation_messaging'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cancellation messaging'),
    ];

    $form['cancellation_messaging']['desktop_cancellation_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Desktop cancellation message'),
      '#default_value' => $this->configuration['desktop_cancellation_message']['value'] ?? '',
      '#format' => $this->configuration['desktop_cancellation_message']['format'] ?? 'full_html',
      '#allowed_formats' => ['full_html'],
      '#help' => $this->t('Appears on desktop devices: A) Within the sidebar on step 1. B) Within the sidebar on steps 2 onwards if "monthly" payment frequency is selected.'),
    ];

    $form['cancellation_messaging']['mobile_cancellation_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Mobile cancellation message'),
      '#default_value' => $this->configuration['mobile_cancellation_message']['value'] ?? '',
      '#format' => $this->configuration['mobile_cancellation_message']['format'] ?? 'full_html',
      '#allowed_formats' => ['full_html'],
      '#help' => $this->t('Appears on mobile and tablet devices: A) At the bottom of step 1. B) At the bottom of steps 2 & 3 if "monthly" payment frequency is selected.'),
    ];

    $form['impact_statistics'] = [
      '#type' => 'fieldset',
    ];

    $form['impact_statistics']['impact_statistics_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Impact Statistics'),
      '#default_value' => $this->configuration['impact_statistics']['value'] ?? '',
      '#format' => 'restricted_html',
      '#allowed_formats' => ['restricted_html'],
      '#help' => $this->t('Suitable for monthly donation Forms. Displays at the top of the donation form.'),
    ];

    $form['impact_statistics']['impact_statistics_help'] = [
      '#markup' => '<p>Use &lt;span&gt; tags to make sections of text bold, for example:</p><p>I want &lt;span&gt;this section&lt;/span&gt; to be bold</p>',
    ];

    // Initial state.
    $customerFieldsRequired = FALSE;

    // Is this form a new Webform handler.
    $build_info = $form_state->getBuildInfo();
    $is_new_handler = FALSE;
    if ($build_info['callback_object'] instanceof WebformHandlerAddForm) {
      $is_new_handler = TRUE;
    }

    // Get lightweight plugin definitions.
    // @todo Use DI.
    $payment_frequency_definitions = \Drupal::service('plugin.manager.payment_frequency')->getDefinitions();
    $payment_provider_definitions = \Drupal::service('plugin.manager.payment_provider')->getDefinitions();

    foreach ($payment_frequency_definitions as $payment_frequency_name => $payment_frequency) {

      $form[$payment_frequency_name] = [
        '#type' => 'fieldset',
        '#title' => $payment_frequency['label'],
      ];

      $payment_frequency_payment_methods = [];
      foreach ($payment_provider_definitions as $payment_provider_id => $payment_provider_definition) {
        if ($payment_provider_definition['payment_frequency'] === $payment_frequency_name) {
          $payment_frequency_payment_methods[$payment_provider_id] = $payment_provider_definition;
        }
      }

      if (empty($payment_frequency_payment_methods)) {
        $form[$payment_frequency_name]['message'] = [
          '#type' => 'markup',
          '#markup' => $this->t('No payment methods available for :payment_type_label. Please enable one on the site first.', [
            ':payment_type_label' => $payment_frequency['label'],
          ]),
        ];
      }
      else {

        $form[$payment_frequency_name]['option_label'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Option label'),
          '#default_value' => $this->configuration[$payment_frequency_name]['option_label'] ?? $payment_frequency['default_option_label'],
        ];

        $form[$payment_frequency_name]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enabled'),
          '#default_value' => $this->configuration[$payment_frequency_name]['enabled'],
        ];

        $form[$payment_frequency_name]['new_frequency'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('New'),
          '#default_value' => $this->configuration[$payment_frequency_name]['new_frequency'],
        ];

        $form[$payment_frequency_name]['frequency_default'] = [
          '#type' => 'radio',
          '#title' => $this->t('Default'),
          '#return_value' => $payment_frequency_name,
          '#description' => $this->t('Set :payment_type_label as the default selected payment frequency', [
            ':payment_type_label' => $payment_frequency['label'],
          ]),
          '#parents' => [
            'settings',
            'frequency_default',
          ],
          '#default_value' => $this->configuration['frequency_default'] == $payment_frequency_name ? $payment_frequency_name : NULL,
        ];

        $form[$payment_frequency_name]['payment_methods'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Payment methods'),
        ];

        $default_payment_method = !empty($this->configuration[$payment_frequency_name]['default_payment_method'])
          ? $this->configuration[$payment_frequency_name]['default_payment_method']
          : NULL;

        foreach ($payment_frequency_payment_methods as $payment_provider_id => $payment_provider_definition) {

          // Only proceed the lookup if a match is not already found.
          if ($customerFieldsRequired === FALSE) {
            // See if $form_state has enabled value for given payment method.
            $paymentProviderEnabledValue = $form_state->getValue([
              'settings',
              $payment_frequency_name,
              'payment_methods',
              $payment_provider_id,
              'enabled',
            ], NULL);
            // Validate customerFieldsRequired from definition.
            if ($paymentProviderEnabledValue !== NULL) {
              // Now make sure it's enabled.
              if ($payment_provider_definition['requiresCustomerFields'] === TRUE && $paymentProviderEnabledValue === 1) {
                // We've found a match so mark customer fields as required.
                $customerFieldsRequired = TRUE;
              }
            }
            // Fallback to a config check if NULL.
            else {
              if ($payment_provider_definition['requiresCustomerFields'] === TRUE && $this->configuration[$payment_frequency_name]['payment_methods'] && in_array($payment_provider_id, $this->configuration[$payment_frequency_name]['payment_methods'])) {
                // We've found a match so mark customer fields as required.
                $customerFieldsRequired = TRUE;
              }
            }
          }

          if ($is_new_handler) {
            $payment_provider_enabled = $payment_provider_definition['enableByDefault'] ?? FALSE;
          }
          else {
            $payment_provider_enabled = $this->configuration[$payment_frequency_name]['payment_methods'] && in_array($payment_provider_id, $this->configuration[$payment_frequency_name]['payment_methods']);
          }

          $form[$payment_frequency_name]['payment_methods'][$payment_provider_id] = [
            '#type' => 'fieldset',
            '#title' => $payment_provider_definition['label'],
            'enabled' => [
              '#type' => 'checkbox',
              '#title' => $this->t('Enabled'),
              '#default_value' => $payment_provider_enabled,
              '#ajax' => [
                'callback' => [$this, 'customerFieldsRequiredAjaxCallback'],
                'wrapper' => 'customer-fields-ajax-container',
              ],
            ],
            'default' => [
              '#type' => 'radio',
              '#title' => $this->t('Default'),
              '#return_value' => $payment_provider_id,
              '#default_value' => $default_payment_method === $payment_provider_id ? $payment_provider_id : NULL,
              '#parents' => [
                'settings',
                $payment_frequency_name,
                'default_payment_method',
              ],
            ],
          ];
        }

        $form[$payment_frequency_name]['amounts'] = [
          '#type' => 'details',
          '#title' => $this->t('Amounts'),
          '#open' => TRUE,
        ];

        // Upselling is for recurring payments only.
        if ('recurring' === $payment_frequency_name) {
          $form[$payment_frequency_name]['upsell'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Upsell message'),
            '#default_value' => $this->configuration[$payment_frequency_name]['upsell'] ?? '',
          ];
        }

        $amount = 6;

        if ($payment_frequency['has_duration'] ?? FALSE) {
          $stripe_price_code = [
            '#type' => 'textfield',
            '#title' => $this->t('Stripe price code'),
            '#description' => $this->t('key from stripe product catalogue (also called fare id), eg: price_xxxxxxxxxxxxxxxxxxxxxxxx.  Only required for fixed price subscriptions'),
          ];
        }
        else {
          $stripe_price_code = [];
        }

        for ($i = 0; $i < $amount; $i++) {
          $number = $i;
          $number++;
          $form[$payment_frequency_name]['amounts'][$i] = [
            '#type' => 'details',
            '#open' => FALSE,
            '#title' => $this->t('Amount option @number', [
              '@number' => $number,
            ]),
            'amount' => [
              '#type' => 'textfield',
              '#title' => $this->t('Amount'),
              '#size' => 5,
            ],
            'image' => [
              '#type' => 'entity_autocomplete',
              '#title' => $this->t('Image'),
              '#target_type' => 'media',
              '#selection_settings' => [
                'target_bundles' => [
                  'image',
                  'assetbank_image',
                ],
              ],
            ],
            'icon' => [
              '#type' => 'entity_autocomplete',
              '#title' => $this->t('Icon'),
              '#target_type' => 'media',
              '#selection_settings' => [
                'target_bundles' => [
                  'image',
                  'assetbank_image',
                ],
              ],
            ],
            'benefit' => [
              '#type' => 'textfield',
              '#title' => $this->t('Benefit'),
            ],
            'body' => [
              '#type' => 'textarea',
              '#title' => $this->t('Body'),
            ],
            'stripe_price_code' => $stripe_price_code,
            'default' => [
              '#type' => 'radio',
              '#title' => $this->t('Default'),
              '#parents' => [
                'settings',
                $payment_frequency_name,
                'default_amount',
              ],
              '#return_value' => $i,
              '#default_value' => $this->configuration[$payment_frequency_name]['default_amount'] == $i ? $i : NULL,
            ],
          ];

          if (isset($this->configuration[$payment_frequency_name]['amounts'][$i])) {
            $form[$payment_frequency_name]['amounts'][$i]['amount']['#default_value'] = $this->configuration[$payment_frequency_name]['amounts'][$i]['amount'] ?? '';
            $form[$payment_frequency_name]['amounts'][$i]['benefit']['#default_value'] = $this->configuration[$payment_frequency_name]['amounts'][$i]['benefit'] ?? '';
            $form[$payment_frequency_name]['amounts'][$i]['body']['#default_value'] = $this->configuration[$payment_frequency_name]['amounts'][$i]['body'] ?? '';
            $form[$payment_frequency_name]['amounts'][$i]['stripe_price_code']['#default_value'] = $this->configuration[$payment_frequency_name]['amounts'][$i]['stripe_price_code'] ?? NULL;

            $image = $this->configuration[$payment_frequency_name]['amounts'][$i]['image'] ?? '';
            if ($image) {
              $image = Media::load($image);
              $form[$payment_frequency_name]['amounts'][$i]['image']['#default_value'] = $image;
            }

            $icon = $this->configuration[$payment_frequency_name]['amounts'][$i]['icon'] ?? '';
            if ($icon) {
              $icon = Media::load($icon);
              $form[$payment_frequency_name]['amounts'][$i]['icon']['#default_value'] = $icon;
            }
          }
        }

        // Allow other amount.
        // @todo grant access when JS and styling support non-other selection.
        $form[$payment_frequency_name]['allow_other_amount'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Allow other amount'),
          '#access' => FALSE,
          '#default_value' => $this->configuration[$payment_frequency_name]['allow_other_amount'] ?? TRUE,
        ];

        // Set minimum amount.
        $form[$payment_frequency_name]['minimum_amount'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Minimum other amount'),
          '#default_value' => $this->configuration[$payment_frequency_name]['minimum_amount'] ?? '2',
        ];

        // Progress donation amount message.
        $form[$payment_frequency_name]['progress_donation_message'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Progress donation message'),
          '#description' => $this->t('Available tokens: [donation:currency-sign], [donation:amount]'),
          '#default_value' => $this->configuration[$payment_frequency_name]['progress_donation_message'] ?? DonationService::getDefaultPaymentFrequencyProgressMessage($payment_frequency_name),
        ];

        // Some payment frequencies can have a fixed duration.
        if ($payment_frequency['has_duration'] ?? FALSE) {

          $duration = 6;

          for ($i = 0; $i < $duration; $i++) {
            $number = $i;
            $number++;
            $form[$payment_frequency_name]['durations'][$i] = [
              '#type' => 'details',
              '#open' => FALSE,
              '#title' => $this->t('Duration option @number', [
                '@number' => $number,
              ]),
              'duration' => [
                '#type' => 'textfield',
                '#title' => $this->t('Duration (months)'),
                '#size' => 5,
              ],
              'default' => [
                '#type' => 'radio',
                '#title' => $this->t('Default'),
                '#parents' => [
                  'settings',
                  $payment_frequency_name,
                  'default_duration',
                ],
                '#return_value' => $i,
                '#default_value' => $this->configuration[$payment_frequency_name]['default_duration'] == $i ? $i : NULL,
              ],
            ];

            if (isset($this->configuration[$payment_frequency_name]['durations'][$i])) {
              $form[$payment_frequency_name]['durations'][$i]['duration']['#default_value'] = $this->configuration[$payment_frequency_name]['durations'][$i]['duration'] ?? '';
            }
          }
        }

      }
    }

    $name_options = [];
    $address_options = [];
    $email_options = [];
    $phone_options = [];
    $elements = $this->getWebform()->getElementsDecodedAndFlattened();

    foreach ($elements as $element_key => $element) {
      switch ($element['#type']) {
        case 'wateraid_forms_webform_name':
          $name_options[$element_key] = $element['#title'];
          break;

        case 'loqate_email_composite':
        case 'email':
          $email_options[$element_key] = $element['#title'];
          break;

        case 'tel':
          $phone_options[$element_key] = $element['#title'];
          break;

        default:
          // Capture all kinds of address fields.
          if (strpos($element['#type'], 'webform_address') === 0 || $element['#type'] === 'pca_address_php') {
            $address_options[$element_key] = $element['#title'];
          }
      }
    }

    $form['customer_fields'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Customer fields'),
      '#description' => $this->t('Set the fields that will be used to collect customer data. This can sometimes be passed to the payment providers.'),
      '#attributes' => [
        'id' => 'customer-fields-ajax-container',
      ],
    ];

    $form['customer_fields']['customer_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Customer name'),
      '#options' => $name_options,
      '#default_value' => $this->configuration['customer_fields']['customer_name'],
      '#required' => $customerFieldsRequired,
    ];

    $form['customer_fields']['customer_address'] = [
      '#type' => 'select',
      '#title' => $this->t('Customer address'),
      '#options' => $address_options,
      '#default_value' => $this->configuration['customer_fields']['customer_address'],
      '#required' => $customerFieldsRequired,
    ];

    $form['customer_fields']['customer_email'] = [
      '#type' => 'select',
      '#title' => $this->t('Customer email'),
      '#options' => $email_options,
      '#default_value' => $this->configuration['customer_fields']['customer_email'],
      '#required' => $customerFieldsRequired,
    ];

    $form['customer_fields']['customer_phone'] = [
      '#type' => 'select',
      '#title' => $this->t('Customer phone'),
      '#options' => $phone_options,
      '#default_value' => $this->configuration['customer_fields']['customer_phone'],
      '#required' => $customerFieldsRequired,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValues();

    $this->configuration['frequency_default'] = $values['frequency_default'];
    // @todo Investigate undefined index notice.
    $this->configuration['currency'] = $values['currency'];
    $this->configuration['default_fund_code'] = $values['default_fund_code'];
    $this->configuration['default_package_code'] = $values['default_package_code'];
    $this->configuration['customer_fields'] = $values['customer_fields'];
    $this->configuration['desktop_cancellation_message'] = $values['cancellation_messaging']['desktop_cancellation_message'];
    $this->configuration['mobile_cancellation_message'] = $values['cancellation_messaging']['mobile_cancellation_message'];
    $this->configuration['impact_statistics'] = $values['impact_statistics']['impact_statistics_text'];

    foreach ($this->donationService->getPaymentFrequencies() as $frequency_name => $frequency) {
      if (isset($values[$frequency_name])) {

        // Filter out non-enabled payment methods.
        $enabled_methods = array_filter($values[$frequency_name]['payment_methods'], static function ($value) {
          return !empty($value['enabled']);
        });

        $this->configuration[$frequency_name] = $values[$frequency_name];
        $this->configuration[$frequency_name]['payment_methods'] = array_keys($enabled_methods);
        $this->configuration[$frequency_name]['enabled'] = $values[$frequency_name]['enabled'];

        // Remove any whitespace around amounts.
        foreach ($this->configuration[$frequency_name]['amounts'] as &$amount) {
          $amount['amount'] = trim($amount['amount']);
        }
        $this->configuration[$frequency_name]['minimum_amount'] = trim($this->configuration[$frequency_name]['minimum_amount']);
      }
    }
    $this->configuration['recurring']['upsell'] = $values['recurring']['upsell'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary(): array {
    // We don't need a summary really, but need to override it because the
    // parent method throws a PHP Notice if the Theme Hook isn't created...
    return [];
  }

  /**
   * AJAX callback for customer fields field set.
   *
   * @param mixed[] $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed[]
   *   The section of the form that is AJAXified.
   */
  public function customerFieldsRequiredAjaxCallback(array &$form, FormStateInterface $form_state): array {
    return $form['settings']['customer_fields'];
  }

  /**
   * Get durations.
   *
   * @return array
   *   The durations.
   */
  public function getDurations(): array {
    $durations_full = [];

    foreach ($this->donationService->getPaymentFrequencies() as $payment_frequency_name => $payment_frequency) {
      if (!empty($this->configuration[$payment_frequency_name]['enabled'])) {
        $durations = [];
        if (array_key_exists('durations', $this->configuration[$payment_frequency_name])) {
          foreach ($this->configuration[$payment_frequency_name]['durations'] as $duration_details) {
            if (!empty($duration_details['duration'])) {
              $durations[$duration_details['duration']] = [
                'label' => $this->formatPlural($duration_details['duration'], '@count month', '@count months'),
              ];
            }
          }

          $durations_full[$payment_frequency_name] = [
            'label' => $this->configuration[$payment_frequency_name]['option_label'] ?? $payment_frequency->getUiLabel(),
            'durations' => $durations,
          ];
        }
      }

    }

    return $durations_full;
  }

  /**
   * Placeholder method for getting frequency/amounts settings.
   *
   * @return mixed[]
   *   An array of amounts by frequency.
   *
   * @todo replace with perhaps a trait for donation configuration.
   */
  public function getAmounts(): array {
    $amounts_full = [];

    /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
    $currency = Currency::load($this->getCurrency());

    foreach ($this->donationService->getPaymentFrequencies() as $payment_frequency_name => $payment_frequency) {
      if (!empty($this->configuration[$payment_frequency_name]['enabled'])) {
        $amounts = [];
        foreach ($this->configuration[$payment_frequency_name]['amounts'] as $amount_details) {
          if (!empty($amount_details['amount'])) {
            $amount_label = $this->getCurrencyValue($currency, $amount_details['amount']);
            $amounts[$amount_details['amount']] = [
              'benefit' => $amount_details['benefit'],
              // @todo Use formatter from currency module.
              'label' => $amount_label,
              'stripePriceCode' => $amount_details['stripe_price_code'] ?? NULL,
            ];

            $image_file = NULL;
//            if (!empty($amount_details['image'])) {
//              /** @var \Drupal\media\Entity\Media $image */
//              $image = Media::load($amount_details['image']);
//              $image_fid = $image->getSource()->getSourceFieldValue($image);
//              $image_file = File::load($image_fid);
//            }
//
            $icon_file = NULL;
//            if (!empty($amount_details['icon'])) {
//              /** @var \Drupal\media\Entity\Media $icon */
//              $icon = Media::load($amount_details['icon']);
//              $icon_fid = $icon->getSource()->getSourceFieldValue($icon);
//              $icon_file = File::load($icon_fid);
//            }

            /** @var \Drupal\Core\Render\Renderer $renderer */
            $renderer = \Drupal::service('renderer');
            $element = [
              '#theme' => 'wateraid_donation_forms_benefit',
              '#amount' => $amount_label,
              '#image' => $image_file?->getFileUri(),
              '#icon' => $icon_file?->getFileUri(),
              '#benefit' => $amount_details['benefit'] ?? NULL,
              '#body' => $amount_details['body'] ?? NULL,
            ];

            $amounts[$amount_details['amount']]['renderedBenefit'] = $renderer->render($element);
          }
        }

        $payment_method_max = [];
        $payment_provider_definitions = \Drupal::service('plugin.manager.payment_provider')->getDefinitions();
        foreach ($this->configuration[$payment_frequency_name]['payment_methods'] as $payment_method) {
          if (isset($payment_provider_definitions[$payment_method])) {
            $payment_method_max[$payment_method] = $payment_provider_definitions[$payment_method]['paymentUpperLimit'];
          }
        }

        $amounts_full[$payment_frequency_name] = [
          'label' => $this->configuration[$payment_frequency_name]['option_label'] ?? $payment_frequency->getUiLabel(),
          'amounts' => $amounts,
          'payment_methods' => $this->configuration[$payment_frequency_name]['payment_methods'],
          'payment_methods_max' => $payment_method_max,
          'allow_other_amount' => TRUE,
          'minimum_amount' => !empty($this->configuration[$payment_frequency_name]['minimum_amount']) ? $this->configuration[$payment_frequency_name]['minimum_amount'] : 0,
        ];
      }
    }

    return $amounts_full;
  }

  /**
   * Helper to format the currency as required.
   *
   * @param \Drupal\currency\Entity\CurrencyInterface $currency
   *   The currency.
   * @param string $amount
   *   The amount to format.
   *
   * @return string
   *   A currency formatted as requested in WMS-2631.
   */
  public function getCurrencyValue(CurrencyInterface $currency, string $amount = ''): string {
    $return = '';

    if ($amount) {
      switch ($currency->id()) {
        case 'JPY':
          $return = $currency->getSign() . number_format($amount, 0, '.', ',');
          break;

        case 'SEK':
          $return = number_format($amount, 0, '.', ' ') . ' kr';
          break;

        default:
          $return = $currency->getSign() . $amount;
          break;

      }
    }

    return $return;
  }

  /**
   * Get defaults.
   *
   * @return mixed[]
   *   Array of defaults.
   */
  public function getAmountDefaults(): array {
    $amount_defaults = [];

    $payment_frequencies = $this->donationService->getPaymentFrequencies();

    if (!empty($this->configuration['frequency_default'])) {
      $amount_defaults['frequency_default'] = $this->configuration['frequency_default'];
    }
    else {
      $amount_defaults['frequency_default'] = key($payment_frequencies);
    }

    foreach ($payment_frequencies as $payment_frequency_name => $payment_frequency) {
      // Check the config exists before continuing.
      if (isset($this->configuration[$payment_frequency_name])) {
        $frequency_config = $this->configuration[$payment_frequency_name];

        // Get the default amount index.
        $amount_index = $frequency_config['default_amount'] ?? key($frequency_config['amounts']);

        // Get the default amount key from the index.
        if (isset($frequency_config['amounts'][$amount_index]['amount'])) {
          $amount_defaults[$payment_frequency_name]['default_amount'] = $frequency_config['amounts'][$amount_index]['amount'];
        }

        // Method does not use index so just set default to value.
        $amount_defaults[$payment_frequency_name]['default_payment_method'] = !empty($frequency_config['default_payment_method']) ? $frequency_config['default_payment_method'] : reset($frequency_config['payment_methods']);
      }
    }

    return $amount_defaults;
  }

  /**
   * Get durations defaults.
   *
   * @return array
   *   The duration defaults.
   */
  public function getDurationDefaults(): array {
    $duration_defaults = [];

    $payment_frequencies = $this->donationService->getPaymentFrequencies();

    foreach ($payment_frequencies as $payment_frequency_name => $payment_frequency) {
      // Check the config exists before continuing.
      if (isset($this->configuration[$payment_frequency_name])) {
        $frequency_config = $this->configuration[$payment_frequency_name];

        if (array_key_exists('durations', $frequency_config)) {

          // Get the default duration index.
          $duration_index = $frequency_config['default_duration'] ?? key($frequency_config['durations']);

          // Get the default duration key from the index.
          if (isset($frequency_config['durations'][$duration_index]['duration'])) {
            $duration_defaults[$payment_frequency_name]['default_duration'] = $frequency_config['durations'][$duration_index]['duration'];
          }

          // Method does not use index so just set default to value.
          $duration_defaults[$payment_frequency_name]['default_payment_method'] = !empty($frequency_config['default_payment_method']) ? $frequency_config['default_payment_method'] : reset($frequency_config['payment_methods']);
        }
      }
    }

    return $duration_defaults;
  }

  /**
   * Get defaults with updated form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed[]
   *   Array of defaults.
   */
  public function getAmountDefaultState(FormStateInterface $form_state): array {
    // Get default options.
    $amount_defaults_all = $this->getAmountDefaults();
    $duration_defaults_all = $this->getDurationDefaults();

    // Add the default duration to each frequency.
    foreach ($amount_defaults_all as $frequency_name => $amount_values) {
      if (isset($duration_defaults_all[$frequency_name])) {
        $amount_defaults_all[$frequency_name]['default_duration'] = $duration_defaults_all[$frequency_name]['default_duration'];
      }
    }

    // Attempt to get user selected options from form_state storage.
    $selected_frequency = $form_state->get(DonationsWebformAmount::STORAGE_FREQUENCY);
    $selected_amount = $form_state->get(DonationsWebformAmount::STORAGE_AMOUNT);
    $selected_payment_method = $form_state->get(DonationsWebformPayment::STORAGE_PAYMENT_METHOD);
    $selected_duration = $form_state->get(DonationsWebformAmount::STORAGE_DURATION);

    // Determine default form values based on availability of user selection.
    $default_frequency = $selected_frequency ?: $amount_defaults_all['frequency_default'];
    $default_amount = $selected_amount ?: $amount_defaults_all[$default_frequency]['default_amount'];
    $default_payment_method = $selected_payment_method ?: $amount_defaults_all[$default_frequency]['default_payment_method'];
    $default_duration = NULL;
    if (isset($duration_defaults_all[$default_frequency]['default_duration'])) {
      $default_duration = $selected_duration ?: $duration_defaults_all[$default_frequency]['default_duration'];
    }

    // Override defaults with user selections.
    $amount_defaults_all['frequency_default'] = $default_frequency;
    $amount_defaults_all[$default_frequency]['default_amount'] = $default_amount;
    $amount_defaults_all[$default_frequency]['$default_payment_method'] = $default_payment_method;
    $amount_defaults_all[$default_frequency]['default_duration'] = $default_duration;

    return $amount_defaults_all;
  }

  /**
   * Get the impact statistics text from the handler.
   *
   * @return Drupal\Component\Render\MarkupInterface
   *   The imact statistics text or an empty string if not set.
   */
  public function getImpactStatistics(): MarkupInterface|string {
    $markup = "";

    if (array_key_exists('impact_statistics', $this->configuration)) {
      $message_content = $this->configuration['impact_statistics']['value'];
      $message_format = $this->configuration['impact_statistics']['format'];
      $markup = check_markup($message_content, $message_format);
    }

    return $markup;
  }

  /**
   * Get frequencies that have 'New' checked.
   *
   * @return array
   *   The array of frequencies that are new.
   */
  public function getNewFrequencies(): array {
    $new_frequencies = [];

    foreach ($this->donationService->getPaymentFrequencies() as $payment_frequency_name => $payment_frequency) {
      if (!empty($this->configuration[$payment_frequency_name]['enabled'])) {
        if (array_key_exists('new_frequency', $this->configuration[$payment_frequency_name])) {
          if ($this->configuration[$payment_frequency_name]['new_frequency']) {
            $new_frequencies[] = $payment_frequency_name;
          }
        }
      }

    }

    return $new_frequencies;
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission): void {
    $form['#attached']['library'][] = 'wateraid_donation_forms/wateraid_donation_forms';
    $form['#attached']['drupalSettings']['wateraidDonationForms']['webform_id'] = $this->getWebform()->id();
    $form['#attached']['drupalSettings']['wateraidDonationForms']['amounts'] = $this->getAmounts();
    $form['#attached']['drupalSettings']['wateraidDonationForms']['amount_defaults'] = $this->getAmountDefaultState($form_state);
    $form['#attached']['drupalSettings']['wateraidDonationForms']['webfrom_sid'] = $this->getWebformSubmission()->id();
    $form['#attached']['drupalSettings']['wateraidDonationForms']['country'] = \Drupal::config('system.date')->get('country.default');

    if ($message = $this->configuration['recurring']['upsell'] ?? '') {
      $form['#attached']['library'][] = 'wateraid_donation_forms/wateraid_donation_forms.upsell';
      $form['#attached']['drupalSettings']['wateraidDonationForms']['upsell']['tag'] = 'wateraid-donation-amount-upsell';
      $form['#attached']['drupalSettings']['wateraidDonationForms']['upsell']['message'] = $message;
    }

    /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
    $currency = Currency::load($this->getCurrency());
    $form['#attached']['drupalSettings']['wateraidDonationForms']['currency'] = $currency->getCurrencyCode();
    $form['#attached']['drupalSettings']['wateraidDonationForms']['currency_step'] = $currency->getRoundingStep();
    $form['#attached']['drupalSettings']['wateraidDonationForms']['currency_sign'] = $currency->getSign();

    if (!empty($form_state->getValues())) {
      $form['#attached']['drupalSettings']['wateraidDonationForms']['contact_details'] = $this->getContactMetaDetails($form_state);
    }

    // Add payment message in to Payment step (if it exists).
    if (isset($form['elements']['payment_type'])) {
      // Only show the message if we have the frequency, currency sign and
      // amount so that we don't show a message with unreplaced tokens.
      if ($currency->getSign()
        && $form_state->has(DonationsWebformAmount::STORAGE_FREQUENCY)
        && $form_state->has([DonationsWebformAmount::STORAGE_AMOUNT])
        && $form_state->has([DonationsWebformAmount::STORAGE_DURATION])
      ) {
        // Get the correct donation message for the selected frequency.
        $payment_message = $this->configuration[$form_state->get(DonationsWebformAmount::STORAGE_FREQUENCY)]['progress_donation_message'];

        // Replace the tokens. This doesn't use the webform token service
        // because that requires an EntityInterface context and the pre-existing
        // donation tokens haven't been set up that way.
        $payment_message = str_replace('[donation:currency-sign]', $currency->getSign(), $payment_message);
        $payment_message = str_replace('[donation:amount]', $form_state->get([DonationsWebformAmount::STORAGE_AMOUNT]), $payment_message);
        $payment_message = str_replace('[donation:duration]', $form_state->get([DonationsWebformAmount::STORAGE_DURATION]), $payment_message);
        if ($payment_message) {
          $form['elements']['payment_type']['intro'] = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $payment_message,
            '#weight' => -5,
          ];
        }
      }
    }

    $cancellation_message = $this->getSetting('mobile_cancellation_message') ?? [];
    if (!empty($cancellation_message)) {
      $form['actions']['cancellation_message'] = [
        '#type' => 'processed_text',
        '#text' => $cancellation_message['value'],
        '#format' => $cancellation_message['format'],
        '#prefix' => '<div class="webform-cancellation-message dd-cancellation-message">',
        '#suffix' => '</div>',
        '#weight' => 999,
      ];
    }

    $desktop_cancellation_message = $this->getSetting('desktop_cancellation_message') ?? [];
    if (!empty($cancellation_message) || !empty($desktop_cancellation_message)) {
      $form['#attached']['library'][] = 'wateraid_donation_forms/wateraid_donation_forms.cancellation_messages';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission): void {
    parent::validateForm($form, $form_state, $webform_submission);

    // Get custom "donations_webform_amount" element values from the storage.
    // If empty attempt to get value from request (values can be passed to form
    // step 2 which bypasses the oportunity to enter these values manually)
    $amount = $form_state->get(DonationsWebformAmount::STORAGE_AMOUNT) ?? $this->request->get('val');
    $payment_frequency = $form_state->get(DonationsWebformAmount::STORAGE_FREQUENCY) ?? $this->request->get('fq');
    $payment_duration = $form_state->get(DonationsWebformAmount::STORAGE_DURATION) ?? $this->request->get('dur', '');

    // Get custom "donations_webform_payment" element values from the storage.
    $payment_method = $form_state->get(DonationsWebformPayment::STORAGE_PAYMENT_METHOD);
    $payment_result = $form_state->get(DonationsWebformPayment::STORAGE_PAYMENT_RESULT);
    $payment_details = $form_state->get(DonationsWebformPayment::STORAGE_PAYMENT_DETAILS);

    $payment_response = '';
    if ($form_state->get(DonationsWebformPayment::STORAGE_PAYMENT_RESPONSE)) {
      $payment_response = Json::decode($form_state->get(DonationsWebformPayment::STORAGE_PAYMENT_RESPONSE));
    }

    // Get currency from Webform's 3rd party settings.
    $currency = $this->getCurrency();

    // Prevent payment provider processing when we are not on the last step and
    // under the assumption that we are dealing with a stepped Webform.
    $pages = $form_state->get('pages');
    $current_page = $form_state->get('current_page');
    if ($pages && !empty($pages)) {
      end($pages);
      $last_page_key = key($pages);
      if ($current_page !== $last_page_key) {
        return;
      }
    }

    // Prevent payment provider processing when we have validation errors.
    if (!empty($form_state->getErrors())) {
      return;
    }

    if (!empty($amount) && !empty($payment_method)) {
      // Get the used payment provider & frequency.
      $payment_provider_plugin = $this->donationService->getPaymentProvider($payment_method);
      $payment_frequency_plugin = $this->donationService->getPaymentFrequency($payment_frequency);

      // Check upper limit first and set form error if the amount exceeds it.
      if ($payment_provider_plugin->getUpperLimit() && $amount > $payment_provider_plugin->getUpperLimit()) {
        $form_state->setErrorByName($form['#payment_element_name'], $this->t('We were unable to take the payment as your donation exceeds the maximum limit. Please decrease your request and try again.'));
        return;
      }

      // Prepare payload.
      $params = [
        'amount' => $amount,
        'currency' => $currency,
        'payment_details' => $payment_details,
        'payment_result' => $payment_result,
        'description' => 'Payment by ' . Unicode::ucfirst($payment_method),
        'payment_response' => $payment_response,
        'customer' => $this->getMappedCustomerFieldValues($form_state),
        'webform_submission' => $webform_submission,
      ];

      try {
        try {
          $result = $payment_provider_plugin->processPayment($params, $webform_submission->getWebform());

          // Reset status incase the payment failed before.
          $data[DonationConstants::DONATION_PREFIX . 'status'] = NULL;
          $webform_submission->setData($data);
        }
        catch (UserFacingPaymentException $e) {
          // phpcs:disable
          $form_state->setErrorByName($form['#payment_element_name'], $this->t($e->getMessage()));
          // phpcs:enable
          return;
        }
        if ($result == NULL) {
          throw new PaymentException('Amount not paid');
        }
      }
      catch (PaymentException $e) {
        $data[DonationConstants::DONATION_PREFIX . 'status'] = PaymentException::PAYMENT_FAILURE_STATUS;
        $webform_submission->setData($data);
        // Set a form error - please try again.
        $form_state->setErrorByName($form['#payment_element_name'], $this->t('We were unable to take your payment. Please check your details and try again.'));
        return;
      }

      // Add prefix with additional "donation__" prefix for webform elements
      // to identify data that needs to be exposed.
      $prefix = DonationConstants::DONATION_PREFIX;

      // Extract generic payment data values.
      $payment_data = [];
      $payment_data[$prefix . 'amount'] = $amount;
      $payment_data[$prefix . 'currency'] = $currency;
      $payment_data[$prefix . 'frequency'] = $payment_frequency;
      $payment_data[$prefix . 'frequency_label'] = $payment_frequency_plugin->getUiLabel();
      $payment_data[$prefix . 'duration'] = $payment_duration;
      $payment_data[$prefix . 'payment_method'] = $payment_method;
      $payment_data[$prefix . 'payment_method_label'] = $payment_provider_plugin->getExportLabel();
      $payment_data[$prefix . 'payment_type'] = $payment_provider_plugin->getPaymentType();
      $payment_data[$prefix . 'date'] = $this->getDefaultCountryFormattedDate();
      $payment_data[$prefix . 'fund_code'] = $this->configuration['default_fund_code'];
      $payment_data[$prefix . 'package_code'] = $this->configuration['default_package_code'];
      $payment_data[$prefix . 'fulfillment_letter'] = 'website thank you';

      // Extract payment provider specific data values.
      foreach ($payment_provider_plugin->getPaymentData($params, $result, $webform_submission->getWebform()) as $key => $value) {
        $payment_data[$prefix . $key] = $value;
      }

      // Add normalised payment data back to the data object & assign
      // normalised data object back onto the submission.
      $webform_submission->setData(array_merge($webform_submission->getData(), $payment_data));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission): void {

    $prefix = DonationConstants::DONATION_PREFIX;
    $data = $webform_submission->getData();
    $donation_status = $data[$prefix . 'status'] ?? NULL;

    /** @var \Drupal\webform\WebformRequestInterface $request_handler */
    $request_handler = \Drupal::service('webform.request');
    $webform = $webform_submission->getWebform();
    $source_entity = $webform_submission->getSourceEntity();

    // If the payment fails, redirect to the webform error page.
    if ($donation_status === PaymentException::PAYMENT_FAILURE_STATUS) {
      $redirect_url = $request_handler->getUrl($webform, $source_entity, 'webform.error');
      $form_state->setRedirectUrl($redirect_url);
    }
    // Otherwise, payment is successful so redirect the user to the donation
    // forms confirm path, including any query parameters from the redirect in
    // Form State.
    else {
      $frequency = $data[$prefix . 'frequency'] ?? NULL;
      if ($source_entity instanceof Node) {
        // Use the internal path to ensure prefixes are not added at this stage.
        $confirm_path = Url::fromRoute('entity.node.webform.confirmation', [
          'node' => $source_entity->id(),
        ])->getInternalPath();

        // @todo remove debug.
        $this->getLogger('wateraid_donation_forms')->debug($this->t('Webform Node confirmation path: @path', [
          '@path' => $confirm_path,
        ]));
      }
      else {
        $confirm_path = _wateraid_donation_forms_get_confirm_path($webform);

        // @todo remove debug.
        $this->getLogger('wateraid_donation_forms')->debug($this->t('Webform Submit confirmation path: @path', [
          '@path' => $confirm_path,
        ]));
      }

      $options = [
        'query' => [
          'frequency' => $frequency,
          'token' => $webform_submission->getToken(),
        ],
      ];

      // Grab the options from the existing redirect. These are required, so
      // that we can set the correct token in the query string. For donation
      // webforms, this should always be a \Drupal\Core\Url. If something
      // changes and this isn't the case, we want to avoid a fatal error by
      // trying to call getOptions() on a non-valid variable. While this would
      // mean the token is missing the user will still see a success message on
      // a valid page rather than the fatal error page.
      $old_redirect_url = $form_state->getRedirect();
      if ($old_redirect_url instanceof Url) {
        $old_options = $old_redirect_url->getOptions();
        if (!empty($old_options['query'])) {
          $options['query'] += $old_options['query'];
        }
      }

      // Create the URL and set the redirect.
      $url = Url::fromUri('base:' . $confirm_path, $options);

      // @todo remove debug.
      $this->getLogger('wateraid_donation_forms')->debug($this->t('Redirect URL: @url', [
        '@url' => print_r($url, TRUE),
      ]));
      $form_state->setRedirectUrl($url);
    }
  }

  /**
   * Format an amount using the donation currency.
   *
   * @param string $amount
   *   Unformatted amount.
   * @param string|null $symbol_code
   *   Either symbol or code.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Formatted amount.
   */
  public function formatAmount(string $amount, ?string $symbol_code = NULL): TranslatableMarkup {
    /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
    $currency = Currency::load($this->getCurrency());

    // Format the currency.
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $amount_with_sign */
    $amount_with_sign = $currency->formatAmount($amount);

    if ($symbol_code === 'code') {
      return $amount_with_sign;
    }
    if ($symbol_code === 'symbol') {
      // @todo will not be needed if intl package enabled.
      return $this->t('@currency_sign@amount', $amount_with_sign->getArguments());
    }
    return $amount_with_sign->getArguments()['@amount'];
  }

  /**
   * Get the currency that applies to this webform.
   *
   * @return string
   *   3 character currency code.
   */
  private function getCurrency(): string {
    return $this->getWebform()->getThirdPartySetting('wateraid_donation_forms', 'currency', 'GBP');
  }

  /**
   * Get a date formatted per country spec.
   *
   * @return string
   *   The current formatted time.
   */
  private function getDefaultCountryFormattedDate(): string {

    $default_country = $this->configFactory->get('system.date')->get('country.default');

    switch ($default_country) {
      case 'JP':
      case 'SE':
        $default_format = 'Y/m/d';
        break;

      case 'US':
        $default_format = 'm/d/Y';
        break;

      default:
        $default_format = 'd/m/Y';
    }

    return $this->dateFormatter->format(time(), 'custom', $default_format);
  }

  /**
   * Helper function to extract customer field data.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return mixed[]
   *   An array of customer fields.
   */
  private function getMappedCustomerFieldValues(FormStateInterface $form_state): array {
    $customer_fields = [];
    // Fetch mapped customer field values.
    foreach ($this->configuration['customer_fields'] as $customer_field_key => $customer_field_name) {
      if (!empty($customer_field_name)) {
        if ($customer_field_key === 'customer_address') {
          $customer_fields[$customer_field_key] = $this->convertAddress($form_state->getValue($customer_field_name));
        }
        elseif ($customer_field_key === 'customer_email') {
          $customer_fields[$customer_field_key] = $this->convertEmail($form_state->getValue($customer_field_name));
        }
        else {
          $customer_fields[$customer_field_key] = $form_state->getValue($customer_field_name);
        }
      }
    }

    return $customer_fields;
  }

  /**
   * Helper function to pass the meta details to the client side.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return string
   *   Json encoded contact details.
   */
  private function getContactMetaDetails(FormStateInterface $form_state): string {
    $customer_fields = $this->getMappedCustomerFieldValues($form_state);

    $contact_details = [];
    $contact_details += $customer_fields['customer_name'] ?? [];
    $contact_details += $customer_fields['customer_address'] ?? [];
    $contact_details['customer_phone'] = $customer_fields['customer_phone'] ?? '';
    $contact_details['customer_email'] = $customer_fields['customer_email'] ?? '';
    $contact_details['donation_url'] = $this->request->getUri();
    return Json::encode($contact_details);
  }

  /**
   * Convert an address from an LoqatePcaAddressPhp element.
   *
   * @param mixed[]|null $address
   *   The address to be converted.
   *
   * @return mixed[]|null
   *   The converted address.
   */
  protected function convertAddress(?array $address = NULL): ?array {
    if (empty($address['address']) && !empty($address['address_line1'])) {
      $address = [
        'address' => $address['address_line1'],
        'address_2' => $address['address_line2'] ?? '',
        'city' => $address['locality'] ?? '',
        'postal_code' => $address['postal_code'] ?? '',
        'state_province' => $address['administrative_area'] ?? '',
        'country' => $address['country_code'] ?? '',
      ];

      // Attempt to convert the country code.
      try {
        $country_name = $this->countryRepository->get($address['country']);
        $address['country'] = $country_name;
      }
      catch (UnknownCountryException $e) {
        // Do nothing and use the raw value already included in $address.
      }
    }
    return $address;
  }

  /**
   * Extract email addresses from standard or composite email fields.
   *
   * @param string|mixed[]|null $email
   *   The email address to convert.
   *
   * @return string|null
   *   Email address.
   */
  protected function convertEmail(string|array|null $email): ? string {
    if (is_array($email) && array_key_exists('email', $email)) {
      $email = $email['email'];
    }
    return $email;
  }

}
