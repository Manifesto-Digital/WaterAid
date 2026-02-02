<?php

namespace Drupal\webform_bankaccount\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform_bankaccount\DDStartDates;

/**
 * Provides a webform element for bank account details.
 *
 * @FormElement("webform_bankaccount")
 */
class WebformBankAccount extends WebformCompositeBase {

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

    $elements = [];

    $elements['account_name'] = [
      '#type' => 'textfield',
      '#title' => t('Account name'),
      '#attributes' => ['class' => ['account-name']],
      '#required' => TRUE,
      '#value' => '',
    ];

    $elements['account'] = [
      '#type' => 'textfield',
      '#title' => t('Account number'),
      '#attributes' => ['class' => ['account-number']],
      '#input_mask' => '99999999',
      '#required' => TRUE,
      '#value' => '',
    ];

    $elements['sort_code'] = [
      '#type' => 'textfield',
      '#title' => t('Sort Code'),
      '#attributes' => ['class' => ['sort-code']],
      '#input_mask' => '99-99-99',
      '#required' => TRUE,
      '#value' => '',
    ];

    $elements['start_date'] = [
      '#type' => 'radios',
      '#title' => t('Choose your first payment date'),
      '#attributes' => [
        'class' =>
          [
            'start-dates',
            'webform-options-display-three-columns',
          ],
      ],
      '#options' => self::getFirstPaymentDates(),
      '#required' => TRUE,
      '#value' => '',
      '#options_display' => 'three-columns',
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderWebformCompositeFormElement($element) {
    $element = parent::preRenderWebformCompositeFormElement($element);
    // Load the javascript.
    $element['#attached']['library'][] = 'webform_bankaccount/webform_bankaccount';

    // Loqate API key from webform_bankaccount configuration.
    $config = \Drupal::config('webform_bankaccount.loqateapisettings');
    $apiKey = $config->get('api_key');

    // The following code only works when we use the form element for now and
    // not the plugin. So going to remove the activation check and keep it on
    // all the time for now, until we have a config system in place for plugins.
    // if (!empty($element['#pca_active']) && $element['#pca_active']) {.
    $element['#attached']['drupalSettings']['webformBankAccount']['active'] = TRUE;
    $element['#attached']['drupalSettings']['webformBankAccount']['apiKey'] = $apiKey;
    $element['#attached']['drupalSettings']['webformBankAccount']['id'] = $element['#id'];
    // }.
    // Add input mask validation.
    self::addInputMask($element['account']);
    self::addInputMask($element['sort_code']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): void {
    // Do not call parent::validateWebformComposite(), it breaks the other
    // payment providers.
    // Element "start_date" should be re-validated cause of the problem when
    // user leaves donation form open for more than N days,
    // start_date becomes outdated and the first payment date is incorrect.
    $start_date_calculated = array_keys(self::getFirstPaymentDates());

    // Validate when account name is empty.
    $value = $form_state->getValue('donation_amount');
    if ($value['frequency'] === 'recurring' && empty($element["#value"]["account_name"]) && $element["account_name"]["#required"]) {
      WebformElementHelper::setRequiredError($element['account_name'], $form_state);
    }

    // Validate when there is no start date with the filled account details.
    if (!empty($element["#value"]["account_name"]) && !empty($element["#value"]["account"]) && !empty($element["#value"]["sort_code"]) && empty($element["#value"]["start_date"])) {
      $error_message = \Drupal::translation()->translate('Please choose your first payment date.');
      $form_state->setError($element['start_date'], $error_message);
    }

    // Only validate when a start date is given.
    if (!empty($element['start_date']['#value']) && !in_array($element['start_date']['#value'], $start_date_calculated)) {
      $form_state->setError($element['start_date'], t('Your session has timed out. Please choose another direct debit start date.'));
    }
  }

  /**
   * Get start date options.
   *
   * @param string $country_code
   *   ISO 2 character country code.
   * @param \DateTime|null $date
   *   The date to find start dates.
   *
   * @return mixed[]
   *   Options array of start dates.
   *
   * @throws \ReflectionException
   */
  private static function getFirstPaymentDates(string $country_code = 'GB', ?\DateTime $date = NULL): array {
    $start_dates = DDStartDates::startDates($country_code, $date);
    $start_date_options = [];

    /** @var \Drupal\Core\Datetime\DateFormatter $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');

    /** @var \DateTime $start_date */
    foreach ($start_dates as $start_date) {
      $start_date_ts = $start_date->getTimestamp();
      $start_date_options[$date_formatter->format($start_date_ts, 'html_date')] = $date_formatter->format($start_date_ts, 'wateraid_fixed_donation');
    }

    return $start_date_options;
  }

  /**
   * Helper method to add input mask to an element.
   *
   * @param mixed[] $element
   *   Form element.
   */
  private static function addInputMask(array &$element): void {
    // Input mask.
    if (!empty($element['#input_mask'])) {
      // See if the element mask is JSON by looking for 'name':, else assume it
      // is a mask pattern.
      $input_mask = $element['#input_mask'];
      if (preg_match("/^'[^']+'\s*:/", $input_mask)) {
        $element['#attributes']['data-inputmask'] = $input_mask;
      }
      else {
        $element['#attributes']['data-inputmask-mask'] = $input_mask;
      }

      $element['#attributes']['class'][] = 'js-webform-input-mask';
      $element['#attached']['library'][] = 'webform/webform.element.inputmask';
    }
  }

}
