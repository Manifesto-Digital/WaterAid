<?php

namespace Drupal\wateraid_donation_forms\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\currency\Entity\Currency;
use Drupal\wateraid_donation_forms\DisplayModeButtonsTrait;
use Drupal\wateraid_donation_forms\DonationConstants;
use Drupal\wateraid_donation_forms\Element\DonationsWebformAmount as DonationsWebformAmountElement;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'donations amount' element.
 *
 * @WebformElement(
 *   id = "donations_webform_amount",
 *   label = @Translation("Donations amount"),
 *   category = @Translation("WaterAid Donations"),
 *   description = @Translation("Provides a form element to set the donation amount. Requires the 'WaterAid Donations' form handler."),
 *   multiline = FALSE,
 *   composite = TRUE,
 *   states_wrapper = FALSE,
 * )
 */
class DonationsWebformAmount extends WebformCompositeBase {

  use DisplayModeButtonsTrait;

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$element, array &$form, FormStateInterface $form_state) {
    parent::alterForm($element, $form, $form_state);
    // Check if a frequency or amount has already been set in form state.
    $input_frequency = $form_state->get(DonationsWebformAmountElement::STORAGE_FREQUENCY);
    $input_amount = $form_state->get(DonationsWebformAmountElement::STORAGE_AMOUNT);
    $input_duration = $form_state->get(DonationsWebformAmountElement::STORAGE_DURATION);

    if (empty($input_frequency) && empty($input_amount)) {
      // Pull pre-selected frequency and amount options from query parameters.
      $request = \Drupal::request();
      $query_frequency = $request->get('fq');
      $query_amount = $request->get('val');
      $query_duration = $request->get('dur');

      if (empty($input_frequency) && empty($input_amount) && $query_frequency && $query_amount) {
        // Set default values in the form state to override the admin defined
        // defaults in DonationsWebformHandler::getAmountDefaultState.
        $form_state->set(DonationsWebformAmountElement::STORAGE_FREQUENCY, $query_frequency);
        $form_state->set(DonationsWebformAmountElement::STORAGE_AMOUNT, $query_amount);

        // Set duration seperately, sometimes not included.
        if (empty($input_duration) && $query_duration) {
          $form_state->set(DonationsWebformAmountElement::STORAGE_DURATION, $query_duration);
        }
      }
    }

    // Ensure the cache varies by form and url query parameters.
    $form['#cache']['contexts'][] = 'url';
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties(): array {
    $default_properties = parent::getDefaultProperties();
    $default_properties['title'] = $this->t('Amount');
    $default_properties['description'] = $this->t('Donation selection element.');
    $default_properties['amount__placeholder'] = $this->t('Enter amount');
    $default_properties['amount__display_mode'] = self::getDefaultDisplayMode();
    $default_properties['frequency__display_mode'] = self::getDefaultDisplayMode();

    return $default_properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements(): array {
    return DonationsWebformAmountElement::getCompositeElements([]);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['element']['multiple']['#access'] = FALSE;
    $form['element']['multiple_error']['#access'] = FALSE;
    $form['element']['multiple__header']['#access'] = FALSE;
    $form['element']['multiple__header_label']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCompositeElementsTable(array $form, FormStateInterface $form_state): array {
    $element = parent::buildCompositeElementsTable($form, $form_state);
    $display_mode_element = [
      '#type' => 'select',
      '#title' => $this->t('Display mode'),
      '#options' => self::getDisplayModeOptions(),
    ];
    $element['frequency']['settings']['data']['frequency__display_mode'] = $display_mode_element;
    $element['amount']['settings']['data']['amount__display_mode'] = $display_mode_element;
    $element['amount']['title_and_description']['data']['amount__placeholder']['#placeholder'] = $this->t('Enter other amount placeholder...');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {

    $value = $this->prepareFormattedItemValue($this->getValue($element, $webform_submission));

    $items = [];
    $items['donation_frequency'] = (string) $this->t('Frequency: @value', ['@value' => $value['frequency']]);
    $items['donation_amount'] = (string) $this->t('Amount: @value', ['@value' => $value['amount']]);
    $items['donation_currency'] = (string) $this->t('Currency: @value', ['@value' => $value['currency']]);

    /** @var \Drupal\wateraid_donation_forms\DonationServiceInterface $donation_service */
    $donation_service = \Drupal::service('wateraid_donation_forms.donation');

    if ($end_date = $donation_service->getFixedPeriodDateEnd($webform_submission)) {
      $items['donation_duration'] = (string) $this->t('Duration: @value months (Last payment: @end_date)', [
        '@value' => $value['duration'],
        '@end_date' => $end_date->format('d/m/Y'),
      ]);
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {

    $value = $this->prepareFormattedItemValue($this->getValue($element, $webform_submission));

    $items = [];
    $items['donation_frequency'] = (string) $this->t('Frequency: @value', ['@value' => $value['frequency']]);
    $items['donation_amount'] = (string) $this->t('Amount: @value', ['@value' => $value['amount']]);
    $items['donation_currency'] = (string) $this->t('Currency: @value', ['@value' => $value['currency']]);

    /** @var \Drupal\wateraid_donation_forms\DonationServiceInterface $donation_service */
    $donation_service = \Drupal::service('wateraid_donation_forms.donation');

    if ($end_date = $donation_service->getFixedPeriodDateEnd($webform_submission)) {
      $items['donation_duration'] = (string) $this->t('Duration: @value months (Last payment: @end_date)', [
        '@value' => $value['duration'],
        '@end_date' => $end_date->format('d/m/Y'),
      ]);
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission): void {
    $data = $webform_submission->getData();
    $element_key = $element['#webform_key'];

    // Pick up data added by the donation webform handler.
    $data[$element_key]['currency'] = $data[DonationConstants::DONATION_PREFIX . 'currency'] ?? 'n/a';
    $data[$element_key]['frequency'] = $data[DonationConstants::DONATION_PREFIX . 'frequency'] ?? 'n/a';
    $data[$element_key]['amount'] = $data[DonationConstants::DONATION_PREFIX . 'amount'] ?? 'n/a';
    $data[$element_key]['duration'] = $data[DonationConstants::DONATION_PREFIX . 'duration'] ?? 'n/a';

    // Trim all modified values to ensure all extraneous whitespace is removed.
    array_walk($data[$element_key], 'trim');
    $data[DonationConstants::DONATION_PREFIX . 'currency'] = trim($data[DonationConstants::DONATION_PREFIX . 'currency']);
    $data[DonationConstants::DONATION_PREFIX . 'frequency'] = trim($data[DonationConstants::DONATION_PREFIX . 'frequency']);
    $data[DonationConstants::DONATION_PREFIX . 'amount'] = trim($data[DonationConstants::DONATION_PREFIX . 'amount']);

    $webform_submission->setData($data);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $options): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options): array {
    return [];
  }

  /**
   * Prepare $value array for formatted output.
   *
   * @param mixed[] $value
   *   An array of donation amount details.
   *
   * @return mixed[]
   *   Processed values
   */
  private function prepareFormattedItemValue(array $value): array {

    /** @var \Drupal\wateraid_donation_forms\DonationServiceInterface $donation_service */
    $donation_service = \Drupal::service('wateraid_donation_forms.donation');
    $payment_frequencies = $donation_service->getPaymentFrequencies();
    if (isset($payment_frequencies[$value['frequency']])) {
      // Override value with label if known.
      $value['frequency'] = $payment_frequencies[$value['frequency']]->getUiLabel();
    }

    /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
    $currency = Currency::load($value['currency']);
    // Trim whitespace from the amount to avoid bcmath errors during formatting.
    $value['amount'] = trim($value['amount']);
    $value['amount'] = $currency ? $currency->formatAmount($value['amount']) : $value['amount'];

    return $value;
  }

}
