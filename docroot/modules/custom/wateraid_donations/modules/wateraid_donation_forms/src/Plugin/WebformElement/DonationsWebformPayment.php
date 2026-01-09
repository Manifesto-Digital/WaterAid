<?php

namespace Drupal\wateraid_donation_forms\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\wateraid_donation_forms\DisplayModeButtonsTrait;
use Drupal\wateraid_donation_forms\DonationConstants;
use Drupal\wateraid_donation_forms\DonationsWebformHandlerTrait;
use Drupal\wateraid_donation_forms\Element\DonationsWebformPayment as DonationsWebformPaymentElement;
use Drupal\wateraid_donation_forms\PaymentTypeInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'donations amount' element.
 *
 * @WebformElement(
 *   id = "donations_webform_payment",
 *   label = @Translation("Donations payment"),
 *   category = @Translation("WaterAid Donations"),
 *   description = @Translation("Provides a form element to accept donation payments. Requires the 'WaterAid Donations' form handler."),
 *   multiline = TRUE,
 *   composite = TRUE,
 * )
 */
class DonationsWebformPayment extends WebformCompositeBase {

  use DonationsWebformHandlerTrait;
  use DisplayModeButtonsTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties(): array {
    return [
      'display_mode' => self::getDefaultDisplayMode(),
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements(): array {
    return DonationsWebformPaymentElement::getCompositeElements([]);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    $form['form']['display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display mode'),
      '#options' => self::getDisplayModeOptions(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {
    $value = $this->prepareFormattedItemValue($this->getValue($element, $webform_submission));
    $items = [];
    // Making the payment method more human-readable.
    $payment_methods = $value['payment_methods'];
    $payment_methods = ucfirst(str_replace('_', ' ', $payment_methods));
    $items['payment_method'] = (string) $this->t('Method: @value', ['@value' => $payment_methods]);
    return $items;
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
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {
    $value = $this->prepareFormattedItemValue($this->getValue($element, $webform_submission));
    $items = [];
    $items['payment_method'] = $this->t('Method: @value', ['@value' => $value['payment_methods']]);
    return $items;
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
    $payment_provider = $donation_service->getPaymentProvider($value['payment_methods']);
    // Making the payment method more human-readable.
    if ($payment_provider !== FALSE) {
      // Override value with label if known. "payment_method" not a typo!
      $value['payment_methods'] = $payment_provider->getUiLabel();
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission): void {
    $data = $webform_submission->getData();
    $element_key = $element['#webform_key'];

    // @todo Not a fan of what's going on here, this essentially collates all
    // prefixed data and even if it's not in this plugin's scope of data set.
    // There is however a constraint on collected data and how it's exported.
    // Copy donation data into data associated with this element.
    foreach ($data as $key => $value) {
      // Get data added by the donation webform handler.
      if (str_starts_with($key, DonationConstants::DONATION_PREFIX)) {
        $payment_data_key = substr($key, 10);
        $data[$element_key][$payment_data_key] = $value;
      }
    }

    $webform_submission->setData($data);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $options): array {

    /** @var \Drupal\wateraid_donation_forms\DonationServiceInterface $donation_service */
    $donation_service = \Drupal::service('wateraid_donation_forms.donation');
    $payment_types = $donation_service->getPaymentTypes();
    $webform_id = explode('--', $element['#webform_id'])[0];
    $webform = Webform::load($webform_id);
    $header = [];

    foreach ($payment_types as $payment_type_details) {
      $columns = $this->getColumnMappings($payment_type_details, $webform);

      foreach (array_keys($columns) as $key) {
        if (in_array($key, $header, TRUE)) {
          continue;
        }
        $header[] = $key;
      }
    }

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options): array {
    // @todo Switch this back to ::getConsolidatedExportRecord() once agreed with CRM people.
    return $this->getDuplicatedExportRecord($webform_submission);
  }

  /**
   * Formats a record row for the CRM export.
   *
   * The old but yet currently in use format for export records.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The Webform Submission.
   *
   * @return mixed[]
   *   A record array.
   *
   * @see DonationsWebformPayment::getConsolidatedExportRecord()
   */
  private function getDuplicatedExportRecord(WebformSubmissionInterface $webform_submission): array {
    /** @var \Drupal\wateraid_donation_forms\DonationServiceInterface $donation_service */
    $donation_service = \Drupal::service('wateraid_donation_forms.donation');
    $payment_types = $donation_service->getPaymentTypes();
    $record = [];

    // We still need all columns existing in the export, so process all payment
    // types up front.
    foreach ($payment_types as $payment_type_details) {
      $columns = $this->getColumnMappings($payment_type_details, $webform_submission->getWebform());
      foreach ($columns as $key => $column) {
        // Pre-populate with empty strings so that the columns always match.
        $record[$key] = '';
      }
    }

    // Pre-discovery of payment type, so that we know which columns to populate
    // with actual values.
    $webform_submission_data = $webform_submission->getData();
    $payment_type_index = DonationConstants::DONATION_PREFIX . 'payment_type';
    $payment_type = $webform_submission_data[$payment_type_index] ?? NULL;

    // Process the data per the new correct "payment_type" usage. Prior to the
    // 03.06.01 release this stores the frequency which is wrong. See processing
    // for the former approach in the else statement.
    if ($payment_type !== NULL) {
      foreach ($payment_types as $payment_type_details) {
        // Skip non-relevant payment types.
        if ($payment_type_details->getPluginId() !== $payment_type) {
          continue;
        }
        // Only process data that matches the payment type.
        foreach ($webform_submission_data as $data_key => $data_value) {
          // Only process data that matches the prefix.
          if (str_starts_with($data_key, DonationConstants::DONATION_PREFIX)) {
            $raw_key = str_replace(DonationConstants::DONATION_PREFIX, '', $data_key);
            $search_key = $payment_type_details->getPrefix() . '_' . $raw_key;
            // Check if we have a label variant.
            $label = '_label';
            if (strpos($data_key, $label) === strlen($data_key) - strlen($label)) {
              // Override search key to feed into the original.
              $search_key = str_replace($label, '', $search_key);
              // We need to correct some of the production data.
              if ($data_value === 'Credit/debit') {
                $data_value = 'Credit card';
              }
            }
            if (isset($record[$search_key])) {
              $record[$search_key] = trim($data_value);
            }
          }
        }
      }
    }
    else {
      // The payment type was prior to release of tag 03.06.01 never stored, so
      // fall back to processing it for older records. The former format that we
      // use here as a reference has a prefix "donation__dd_" for recurring and
      // "donation__donation_" for one-off. Yes this is wrong but that is how
      // they were stored. For this we will try and match the data set for
      // either of the prefixes and act accordingly in processing it as its
      // frequency. An amount is very much mandatory, so we will match it on
      // those key values.
      $old_frequency_refs = [
        'dd' => DonationConstants::DONATION_PREFIX . 'dd_amount',
        'donation' => DonationConstants::DONATION_PREFIX . 'donation_amount',
      ];
      // Try and match a one of the amounts.
      foreach ($old_frequency_refs as $data_prefix => $old_frequency_ref) {
        if (isset($webform_submission_data[$old_frequency_ref])) {
          // Proceed data processing.
          foreach ($webform_submission_data as $data_key => $data_value) {
            // Only process data that matches the prefix.
            if (str_starts_with($data_key, DonationConstants::DONATION_PREFIX)) {
              // Match with the already double prefixed data keys in the former
              // implementation.
              $search_key = str_replace(DonationConstants::DONATION_PREFIX, '', $data_key);
              // Now match sub prefix to ensure data set.
              if (str_starts_with($search_key, $data_prefix)) {
                // Check existing record for key.
                if (isset($record[$search_key])) {
                  $record[$search_key] = $data_value;
                }
              }
            }
          }
          // Break out of the loop, we have found a matching frequency.
          break;
        }
      }
    }

    return $record;
  }

  /**
   * Get all the columns based on handler configuration.
   *
   * This is the old format that we are still constraint to for use. Until
   * we have a discussion on transitioning to the new format, we cannot
   * deviate from this format.
   *
   * @param \Drupal\wateraid_donation_forms\PaymentTypeInterface $payment_type_details
   *   Payment type details object.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform instance.
   *
   * @return mixed[]
   *   An array of column detail arrays each of which has a 'key' element.
   *
   * @see DonationsWebformPayment::getConsolidatedColumnMappings()
   */
  private function getColumnMappings(PaymentTypeInterface $payment_type_details, WebformInterface $webform): array {
    $column_mappings = [];

    $common_columns = [
      'currency',
      'amount',
      'date',
      'fulfillment_letter',
      'status',
    ];

    // Process common columns.
    foreach ($common_columns as $column) {
      $column_mappings[$payment_type_details->getPrefix() . '_' . $column] = ['key' => DonationConstants::DONATION_PREFIX . $column];
    }

    // Process payment type specific columns.
    foreach ($payment_type_details->getDataColumns() as $column) {
      $column_mappings[$payment_type_details->getPrefix() . '_' . $column] = ['key' => DonationConstants::DONATION_PREFIX . $column];
    }

    // Special case: remove "customer_id" column for sites that do not use
    // Stripe API for "recurring" CC payments (see WMS-21).
    $customer_id_index = $payment_type_details->getPrefix() . '_customer_id';
    if (isset($column_mappings[$customer_id_index])) {
      $handler = self::getWebformDonationsHandler($webform);
      $config = $handler->getConfiguration();
      if (isset($config['settings']['recurring']['payment_methods'])) {
        $settings = $config['settings']['recurring']['payment_methods'];
        if (in_array('stripe_subscription', $settings) === FALSE) {
          // Remove the customer id column from the output.
          unset($column_mappings[$customer_id_index]);
        }
      }
    }

    return $column_mappings;
  }

  /**
   * Formats a record row for the CRM export.
   *
   * This is the new format we are aiming for to be used, but this still needs
   * discussion with Effra CRM people when we are able to make the switch or/if
   * transition.
   * Also change visibility back to private when in use, phpcs won't let me have
   * this committed.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The Webform Submission.
   *
   * @return mixed[]
   *   A record array.
   *
   * @see DonationsWebformPayment::getDuplicatedExportRecord()
   */
  public function getConsolidatedExportRecord(WebformSubmissionInterface $webform_submission): array {

    /** @var \Drupal\wateraid_donation_forms\DonationServiceInterface $donation_service */
    $donation_service = \Drupal::service('wateraid_donation_forms.donation');
    $payment_types = $donation_service->getPaymentTypes();
    $record = [];

    foreach ($payment_types as $payment_type_details) {
      $columns = $this->getColumnMappings($payment_type_details, $webform_submission->getWebform());

      foreach ($columns as $column) {
        $record[$column['key']] = '';
      }
    }

    foreach ($webform_submission->getData() as $data_key => $data_value) {
      if (isset($record[$data_key])) {
        $record[$data_key] = $data_value;
      }
    }

    return $record;
  }

  /**
   * Get all the columns based on handler configuration.
   *
   * This is a consolidated format where the common columns are not duplicated
   * in the export. However, this needs still needs discussion with Effra CRM
   * people to see how we are transitioning into this format.
   * Until then, we should still use the former column mapping.
   * Also change visibility back to private when in use, phpcs won't let me have
   * this committed.
   *
   * @param \Drupal\wateraid_donation_forms\PaymentTypeInterface $payment_type_details
   *   Payment type details object.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform instance.
   *
   * @return mixed[]
   *   An array of column detail arrays each of which has a 'key' element.
   *
   * @see DonationsWebformPayment::getColumnMappings()
   */
  public function getConsolidatedColumnMappings(PaymentTypeInterface $payment_type_details, WebformInterface $webform): array {

    $column_mappings = [];

    $common_columns = [
      'currency',
      'amount',
      'date',
      'fulfillment_letter',
      'status',
    ];

    // Process common columns.
    foreach ($common_columns as $column) {
      $column_mappings[$column] = ['key' => DonationConstants::DONATION_PREFIX . $column];
    }

    // Process payment type specific columns.
    foreach ($payment_type_details->getDataColumns() as $column) {
      $column_mappings[$column] = ['key' => DonationConstants::DONATION_PREFIX . $column];
    }

    // Special case: remove "customer_id" column for sites that do not use
    // Stripe API for "recurring" CC payments (see WMS-21).
    if (isset($column_mappings['customer_id'])) {
      $handler = self::getWebformDonationsHandler($webform);
      $config = $handler->getConfiguration();
      if (isset($config['settings']['recurring']['payment_methods'])) {
        $settings = $config['settings']['recurring']['payment_methods'];
        if (in_array('stripe_subscription', $settings) === FALSE) {
          // Remove the customer id column from the output.
          unset($column_mappings['customer_id']);
        }
      }
    }

    return $column_mappings;
  }

}
