<?php

namespace Drupal\webform_capture_plus\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\Plugin\WebformElement\WebformAddress;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_capture_plus\WebformAddressCapturePlusTrait;

/**
 * Provides a 'capture plus address' element.
 *
 * @WebformElement(
 *   id = "webform_address_capture_plus_manual",
 *   label = @Translation("Address (Capture Plus) Manual"),
 *   description = @Translation("Provides a form element to collect address information (street, city, state, zip) with manual fallback."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformAddressCapturePlusManual extends WebformAddress {

  use WebformAddressCapturePlusTrait;

  /**
   * {@inheritdoc}
   */
  public function getPluginLabel() {
    $label = parent::getPluginLabel();

    $site = \Drupal::config('system.date')->get('country.default');
    if ($site == 'GB') {
      switch ($this->getPluginId()) {
        case 'webform_address_capture_plus':
          $label = new TranslatableMarkup('Address (Capture Plus) - Unsupported');
          break;

        case 'webform_address':
          $label = new TranslatableMarkup('Address - Unsupported');
          break;
      }
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDescription() {
    $description = parent::getPluginDescription();

    $site = \Drupal::config('system.date')->get('country.default');
    if ($site == 'GB') {
      switch ($this->getPluginId()) {
        case 'webform_address_capture_plus':
          $description = new TranslatableMarkup('Capture Plus is no longer supported. Please use Loqate Address instead.');
          break;

        case 'webform_address':
          $description = new TranslatableMarkup('Address is no longer supported. Please use Loqate Address instead.');
          break;
      }
    }

    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {
    $formatted = parent::formatTextItemValue($element, $webform_submission, $options);

    // Append the PAF validated value as a new line.
    $value = $this->getValue($element, $webform_submission, $options);
    $paf_string = $value['paf'] ? '1' : '0';
    $formatted['paf'] = 'PAF: ' . $paf_string;

    return $formatted;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $options): array {
    if ($this->hasMultipleValues($element)) {
      return parent::buildExportHeader($element, $options);
    }

    $composite_elements = $this->getInitializedCompositeElement($element);
    $header = [];
    foreach (Element::children($composite_elements) as $composite_key) {
      if (!in_array($composite_key, $options['capture_plus_address_options'])) {
        continue;
      }
      $composite_element = $composite_elements[$composite_key];

      if ($options['header_format'] === 'label' && !empty($composite_element['#title'])) {
        $header[] = $composite_element['#title'];
      }
      else {
        $header[] = $composite_key;
      }
    }

    return $this->prefixExportHeader($header, $element, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getExportDefaultOptions(): array {
    return [
      'capture_plus_address_options' => [
        'address' => 'Address',
        'address_2' => 'Address 2',
        'city' => 'City',
        'postal_code' => 'Postcode',
        'state_province' => 'State/province',
        'country' => 'Country',
        'paf' => 'PAF validated',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options): void {
    parent::buildExportOptionsForm($form, $form_state, $export_options);

    $form['capture_plus'] = [
      '#type' => 'details',
      '#title' => $this->t('Address capture plus options'),
      '#open' => TRUE,
      '#weight' => -5,
    ];

    $form['capture_plus']['capture_plus_address_options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Address columns'),
      '#description' => $this->t('Specify which address columns to include in the export.'),
      '#options' => [
        'address' => $this->t('Address'),
        'address_2' => $this->t('Address 2'),
        'city' => $this->t('City'),
        'postal_code' => $this->t('Postcode'),
        'state_province' => $this->t('State/province'),
        'country' => $this->t('Country'),
        'paf' => $this->t('PAF validated'),
      ],
      '#default_value' => $export_options['capture_plus_address_options'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options): array {
    $value = $this->getValue($element, $webform_submission);

    if ($this->hasMultipleValues($element)) {
      $element['#format'] = ($export_options['header_format'] === 'label') ? 'list' : 'raw';
      $export_options['multiple_delimiter'] = PHP_EOL . '---' . PHP_EOL;
      return parent::buildExportRecord($element, $webform_submission, $export_options);
    }

    $record = [];
    $composite_elements = $this->getInitializedCompositeElement($element);
    foreach (Element::children($composite_elements) as $composite_key) {
      if (!in_array($composite_key, $export_options['capture_plus_address_options'])) {
        continue;
      }
      $composite_element = $composite_elements[$composite_key];

      if ($export_options['composite_element_item_format'] === 'label' && $composite_element['#type'] !== 'textfield' && !empty($composite_element['#options'])) {
        $record[] = WebformOptionsHelper::getOptionText($value[$composite_key], $composite_element['#options']);
      }
      // Ensure PAF values are 1 or 0.
      elseif ($composite_key === 'paf') {
        $record[] = isset($value[$composite_key]) ? (int) $value[$composite_key] : 0;
      }
      // Convert country codes to country names.
      elseif ($composite_key === 'country_code') {
        $countries = $this->countryRepository->getList();
        $country_value = NULL;
        if (isset($value[$composite_key])) {
          $country_value = $countries[$value[$composite_key]] ?? $value[$composite_key];
        }
        $record[] = $country_value;
      }
      else {
        $record[] = $value[$composite_key] ?? NULL;
      }
    }
    return $record;
  }

}
