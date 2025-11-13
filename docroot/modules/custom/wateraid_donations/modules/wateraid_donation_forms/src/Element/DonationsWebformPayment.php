<?php

namespace Drupal\wateraid_donation_forms\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wateraid_donation_forms\DisplayModeButtonsTrait;
use Drupal\wateraid_donation_forms\DonationsWebformHandlerTrait;
use Drupal\wateraid_donation_forms\PaymentProviderInterface;
use Drupal\webform\Element\WebformCompositeBase;
use Drupal\webform\Entity\Webform;

/**
 * Provides a webform element for a donations payment element.
 *
 * @FormElement("donations_webform_payment")
 */
class DonationsWebformPayment extends WebformCompositeBase {

  use DonationsWebformHandlerTrait;
  use DisplayModeButtonsTrait;

  /**
   * The storage name for "payment_method" recording.
   */
  public const STORAGE_PAYMENT_METHOD = 'payment_method';

  /**
   * The storage name for "payment_details" recording.
   */
  public const STORAGE_PAYMENT_DETAILS = 'payment_details';

  /**
   * The storage name for "payment_result" recording.
   */
  public const STORAGE_PAYMENT_RESULT = 'payment_result';

  /**
   * The storage name for "payment_response" recording.
   */
  public const STORAGE_PAYMENT_RESPONSE = 'payment_response';

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $info = parent::getInfo();
    unset($info['#theme']);
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): array {
    parent::processWebformComposite($element, $form_state, $complete_form);

    // Help out the webform handler.
    $complete_form['#payment_element_name'] = $element['#name'] ?? '';

    /** @var \Drupal\wateraid_donation_forms\DonationServiceInterface $donation_service */
    $donation_service = \Drupal::service('wateraid_donation_forms.donation');

    /** @var \Drupal\Component\Uuid\UuidInterface $uuid_service */
    $uuid_service = \Drupal::service('uuid');

    /** @var \Drupal\anonymous_token\Access\AnonymousCsrfTokenGenerator $csrf_token */
    $csrf_token = \Drupal::service('anonymous_token.csrf_token');

    if (!empty($element['#webform']) && $webform = Webform::load($element['#webform'])) {

      $handler = self::getWebformDonationsHandler($webform);

      $element['#element_validate'] = [[get_called_class(),
        'validateDonationsWebformPayment',
      ],
      ];

      $amounts = $handler->getAmounts();

      // Get default options.
      $amount_defaults_all = $handler->getAmountDefaultState($form_state);

      $element['payment_methods'] = [
        '#type' => 'container',
      ];

      // Create a hidden form element to capture the payment intent response.
      $element['payment_response'] = [
        '#type' => 'hidden',
        '#value' => NULL,
        '#attributes' => ['id' => ['payment-response-result']],
      ];

      $element['idempotency_key'] = [
        '#type' => 'hidden',
        '#value' => $uuid_service->generate(),
        '#attributes' => ['id' => ['idempotency-key']],
      ];

      // CSRF token hidden field.
      $element['token'] = [
        '#type' => 'hidden',
        '#value' => $csrf_token->get('wateraid_donation_stripe/sca/payment_intent'),
        '#attributes' => ['id' => ['token']],
      ];

      // Create a hidden form element to capture the selected frequency/type.
      $element['payment_frequency'] = [
        '#type' => 'hidden',
        '#value' => $amount_defaults_all['frequency_default'],
        '#attributes' => ['class' => ['wa-donation-payment-selected-type']],
      ];

      // Create a hidden form element to passback client-side payment details.
      $element['payment_result'] = [
        '#type' => 'hidden',
        '#attributes' => ['class' => ['wa-donation-payment-result']],
      ];

      foreach ($amounts as $type => $amount_details) {
        $amount_defaults = $amount_defaults_all[$type];

        /** @var \Drupal\wateraid_donation_forms\PaymentProviderInterface[] $payment_providers */
        $payment_providers = [];
        if (!empty($amount_details['payment_methods'])) {
          foreach ($amount_details['payment_methods'] as $payment_provider_id) {
            $payment_provider = $donation_service->getPaymentProvider($payment_provider_id);
            if ($payment_provider) {
              $payment_providers[$payment_provider_id] = $payment_provider;
            }
          }
        }

        $element['payment_methods'][$type] = [
          '#type' => 'container',
          '#attributes' => [
            'data-donations-type' => $type,
            'class' => ['wa-donations-type'],
          ],
        ];

        // Hide this payment type section if this is not the default type.
        if ($amount_defaults_all['frequency_default'] !== $type) {
          $element['payment_methods'][$type]['#attributes']['style'] = 'display: none';
        }

        if (count($payment_providers) === 1) {

          reset($payment_providers);
          $payment_provider_id = key($payment_providers);
          $payment_provider = $payment_providers[$payment_provider_id];

          $element['payment_methods'][$type]['selection'] = [
            '#type' => 'value',
            '#value' => $payment_provider_id,
          ];

          self::addPaymentProvider($element['payment_methods'][$type], $payment_provider_id, $payment_provider, $form_state, $complete_form, TRUE);
        }
        elseif (count($payment_providers) > 1) {
          $options = [];
          foreach ($payment_providers as $payment_provider_id => $payment_provider) {
            $options[$payment_provider_id] = $payment_provider->getUiLabel();
          }

          $display_mode_class = self::getDisplayModeClassByElement($element);
          $selection_html_id = Html::getUniqueId('wa-donation-method-selection');

          $element['payment_methods'][$type]['selection'] = [
            '#type' => 'donations_webform_buttons',
            '#title' => t('Select payment method'),
            '#attributes' => [
              'class' => [
                'wa-donation-method-selection',
                $display_mode_class,
              ],
              'data-donations-id' => $selection_html_id,
            ],
            '#options' => $options,
            '#default_value' => $amount_defaults['default_payment_method'],
          ];

          $element['payment_methods'][$type]['methods'] = [
            '#type' => 'fieldset',
            '#attributes' => [
              'class' => [
                'wa-donation-methods',
              ],
            ],
            '#title' => t('Payment options for @type payment', ['@type' => ($type === 'one_off' ? t('one-time') : t('monthly'))]),
          ];

          foreach ($payment_providers as $payment_provider_id => $payment_provider) {
            $is_default = $payment_provider_id === $amount_defaults['default_payment_method'];
            self::addPaymentProvider($element['payment_methods'][$type]['methods'], $payment_provider_id, $payment_provider, $form_state, $complete_form, $is_default);
          }
        }
      }
    }

    // Check if the form is using v2.
    $webform_id = $complete_form['#webform_id'];

    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($webform_id);

    // Get the webform style version.
    $style_version = $webform->getThirdPartySetting('wateraid_forms', 'style_version', 'v2');

    if ($style_version == 'v2') {
      $element['#attached']['library'][] = 'wateraid_donation_forms/wateraid_donation_forms.element.payment.v2';
    }

    return $element;
  }

  /**
   * Add a payment provider to the form with necessary wrapper.
   *
   * @param mixed[] $element
   *   Element.
   * @param string $payment_provider_id
   *   Payment provider id.
   * @param \Drupal\wateraid_donation_forms\PaymentProviderInterface $payment_provider
   *   Payment provider.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param mixed[] $complete_form
   *   Complete form.
   * @param bool $is_default
   *   Default flag.
   */
  private static function addPaymentProvider(array &$element, string $payment_provider_id, PaymentProviderInterface $payment_provider, FormStateInterface $form_state, array &$complete_form, bool $is_default): void {
    $element[$payment_provider_id] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'wa-donation-method',
        ],
        'data-donations-method' => $payment_provider_id,
      ],
    ];

    if (!empty($payment_provider->getJsView())) {
      $element[$payment_provider_id]['#attributes']['data-donations-view'] = $payment_provider->getJsView();
    }

    if (!$is_default) {
      $element[$payment_provider_id]['#attributes']['style'] = 'display: none';
    }

    $payment_provider->processWebformComposite($element[$payment_provider_id], $form_state, $complete_form);
  }

  /**
   * Validates a donations_webform_payment element.
   *
   * @param mixed[] $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param mixed[] $complete_form
   *   The complete form.
   *
   * @return mixed[]
   *   The element.
   */
  public static function validateDonationsWebformPayment(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    $selected_payment_frequency = NestedArray::getValue($form_state->getValues(), $element['payment_frequency']['#parents']);
    $payment_result = NestedArray::getValue($form_state->getValues(), $element['payment_result']['#parents']);
    $payment_response = NestedArray::getValue($form_state->getValues(), $element['payment_response']['#parents']);

    // Donation webform amount field must be converted into a single value.
    if (!empty($element['payment_methods']['#parents'])) {
      $payment_method_types = NestedArray::getValue($form_state->getValues(), $element['payment_methods']['#parents']);

      if (!empty($payment_method_types[$selected_payment_frequency]['selection'])) {

        $selected_payment_method = $payment_method_types[$selected_payment_frequency]['selection'];

        if (!empty($payment_method_types[$selected_payment_frequency]['methods'][$selected_payment_method])) {
          $selected_payment_method_details = $payment_method_types[$selected_payment_frequency]['methods'][$selected_payment_method];
        }
        elseif (!empty($payment_method_types[$selected_payment_frequency][$selected_payment_method])) {
          $selected_payment_method_details = $payment_method_types[$selected_payment_frequency][$selected_payment_method];
        }
        else {
          $selected_payment_method_details = [];
        }

        $form_state->set(self::STORAGE_PAYMENT_METHOD, $selected_payment_method);
        $form_state->set(self::STORAGE_PAYMENT_DETAILS, $selected_payment_method_details);
        $form_state->set(self::STORAGE_PAYMENT_RESULT, $payment_result);
        $form_state->set(self::STORAGE_PAYMENT_RESPONSE, $payment_response);

        $form_state->setValueForElement($element['payment_methods'], $selected_payment_method);
      }
    }
    return $element;
  }

}
