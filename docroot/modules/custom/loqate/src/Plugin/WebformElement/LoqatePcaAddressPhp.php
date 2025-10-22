<?php

namespace Drupal\loqate\Plugin\WebformElement;

use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\loqate\PcaAddressFieldMapping\PcaAddressElement;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Loqate address' element.
 *
 * @WebformElement(
 *   id = "pca_address_php",
 *   label = @Translation("Loqate address"),
 *   description = @Translation("Provides a form element to collect address information (street, city, state, zip). Does not use JS widget."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\loqate\Element\LoqatePcaAddress
 */
class LoqatePcaAddressPhp extends WebformCompositeBase {

  /**
   * The country repository.
   */
  protected CountryRepositoryInterface $countryRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): LoqatePcaAddressPhp {
    $self = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $self->countryRepository = new CountryRepository();
    return $self;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginLabel() {
    return \Drupal::moduleHandler()->moduleExists('pca_address') ? $this->t('Basic Loqate address (PHP)') : parent::getPluginLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\loqate\Plugin\Field\FieldType\LoqatePcaAddressItem::schema
   */
  public function initializeCompositeElements(array &$element): void {
    $element['#webform_composite_elements'] = [
      PcaAddressElement::LINE1 => [
        '#title' => $this->getElementProperty($element, PcaAddressElement::LINE1 . '_label'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      PcaAddressElement::LINE2 => [
        '#title' => $this->getElementProperty($element, PcaAddressElement::LINE2 . '_label'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      PcaAddressElement::LOCALITY => [
        '#title' => $this->getElementProperty($element, PcaAddressElement::LOCALITY . '_label'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      PcaAddressElement::ADMINISTRATIVE_AREA => [
        '#title' => $this->getElementProperty($element, PcaAddressElement::ADMINISTRATIVE_AREA . '_label'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      PcaAddressElement::POSTAL_CODE => [
        '#title' => $this->getElementProperty($element, PcaAddressElement::POSTAL_CODE . '_label'),
        '#type' => 'textfield',
        '#maxlength' => 255,
      ],
      PcaAddressElement::COUNTRY_CODE => [
        '#title' => $this->getElementProperty($element, PcaAddressElement::COUNTRY_CODE . '_label'),
        '#type' => 'textfield',
        '#maxlength' => 2,
      ],
      PcaAddressElement::PAF => [
        '#title' => $this->t('PAF validated'),
        '#type' => 'checkbox',
        '#access' => FALSE,
      ],
    ];

    // Copy required value on initalize to allow changes and checks that don't
    // conflict with states and Webform conditions.
    $element['#loqate_required'] = ($element['#required']) ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties(): array {
    return [
      'show_address_fields' => FALSE,
      'allow_manual_input' => TRUE,
      'manual_entry_label' => $this->t('Enter address manually'),
      'find_error_message' => $this->t("You seem to have entered a postcode that we can't recognise. Please try again e.g. SE13 3AW. If your home is a new build, you may need to enter the full address yourself.",),
      'lookup_error_message' => $this->t('There was an error retrieving the selected address. Please try again or enter your address manually.'),
      'change_address_label' => $this->t('Change address'),
      'search_input_label' => $this->t('Go back to postcode search'),
      PcaAddressElement::LINE1 . '_label' => $this->t('Address line 1'),
      PcaAddressElement::LINE2 . '_label' => $this->t('Address line 2',),
      PcaAddressElement::LOCALITY . '_label' => $this->t('Locality'),
      PcaAddressElement::ADMINISTRATIVE_AREA . '_label' => $this->t('Administrative area'),
      PcaAddressElement::COUNTRY_CODE . '_label' => $this->t('Country'),
      PcaAddressElement::POSTAL_CODE . '_label' => $this->t('Postal code'),
      PcaAddressElement::LINE1 . '_required' => TRUE,
      PcaAddressElement::LINE2 . '_required' => FALSE,
      PcaAddressElement::LOCALITY . '_required' => TRUE,
      PcaAddressElement::ADMINISTRATIVE_AREA . '_required' => FALSE,
      PcaAddressElement::COUNTRY_CODE . '_required' => TRUE,
      PcaAddressElement::POSTAL_CODE . '_required' => TRUE,
      PcaAddressElement::LINE1 . '_search_label' => $this->t('Address line 1'),
      PcaAddressElement::POSTAL_CODE . '_search_label' => $this->t('Postal code'),
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['composite'] = [
      '#type' => 'details',
      '#title' => $this->t('Loqate address'),
      '#open' => TRUE,
    ];

    $form['composite']['show_address_fields'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show address fields'),
    ];

    $form['composite']['allow_manual_input'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow manual input'),
    ];

    $form['composite']['labels'] = [
      '#type' => 'details',
      '#title' => $this->t('Address component labels for manual entry'),
      '#open' => FALSE,
    ];

    $form['composite']['search_labels'] = [
      '#type' => 'details',
      '#title' => $this->t('Address component labels for postcode search'),
      '#open' => FALSE,
    ];
    $form['composite']['require'] = [
      '#type' => 'details',
      '#title' => $this->t('Required/optional component configuration'),
      '#open' => FALSE,
    ];

    $fields = [
      PcaAddressElement::LINE1 => $this->t('Address line 1'),
      PcaAddressElement::LINE2 => $this->t('Address line 2',),
      PcaAddressElement::LOCALITY => $this->t('Locality'),
      PcaAddressElement::ADMINISTRATIVE_AREA => $this->t('Administrative area'),
      PcaAddressElement::COUNTRY_CODE => $this->t('Country'),
      PcaAddressElement::POSTAL_CODE => $this->t('Postal code'),
    ];
    foreach ($fields as $field_id => $field_label) {
      $form['composite']['labels'][$field_id . '_label'] = [
        '#type' => 'textfield',
        '#title' => $field_label,
      ];

      // Only Line 1 & postcode are shown for the search stage so only these
      // require custom labels.
      if (in_array($field_id, [PcaAddressElement::LINE1, PcaAddressElement::POSTAL_CODE])) {
        $form['composite']['search_labels'][$field_id . '_search_label'] = [
          '#type' => 'textfield',
          '#title' => $field_label,
        ];
      }

      $form['composite']['require'][$field_id . '_required'] = [
        '#type' => 'checkbox',
        '#title' => $field_label,
      ];
    }

    $form['composite']['text_overrides'] = [
      '#type' => 'details',
      '#title' => $this->t('Text overrides'),
      '#open' => FALSE,
    ];

    $form['composite']['manual_entry_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Manual address label'),
      '#description' => $this->t('The text on the button to trigger manual entry. E.g. "Enter address manually".'),
    ];
    $form['composite']['change_address_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Change address label'),
      '#description' => $this->t('The text on the button to edit a selected address. E.g. "Change address".'),
    ];
    $form['composite']['search_input_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search label'),
      '#description' => $this->t('The text on the button to go back to postcode search. E.g. "Go back to postcode search".'),
    ];
    $form['composite']['find_error_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Error message'),
      '#description' => $this->t('The error to show if a search fails or no results are found.'),
      '#maxlength' => 255,
    ];
    $form['composite']['lookup_error_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lookup error message'),
      '#description' => $this->t('The error to show if the lookup of a specific address fails.'),
      '#maxlength' => 255,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, ?WebformSubmissionInterface $webform_submission = NULL): void {
    parent::prepare($element, $webform_submission);

    // Ensure all default properties are added to the element even when not
    // overridden by specific config.
    foreach ($this->getDefaultProperties() as $default_id => $default_value) {
      if (!isset($element['#' . $default_id])) {
        $element['#' . $default_id] = $default_value;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExportDefaultOptions(): array {
    return [
      'address_columns' => [
        PcaAddressElement::LINE1 => PcaAddressElement::LINE1,
        PcaAddressElement::LINE2 => PcaAddressElement::LINE2,
        PcaAddressElement::LOCALITY => PcaAddressElement::LOCALITY,
        PcaAddressElement::ADMINISTRATIVE_AREA => PcaAddressElement::ADMINISTRATIVE_AREA,
        PcaAddressElement::POSTAL_CODE => PcaAddressElement::POSTAL_CODE,
        PcaAddressElement::COUNTRY_CODE => PcaAddressElement::COUNTRY_CODE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options): void {
    parent::buildExportOptionsForm($form, $form_state, $export_options);

    $form['loqate'] = [
      '#type' => 'details',
      '#title' => $this->t('Loqate address options'),
      '#open' => TRUE,
      '#weight' => -5,
    ];

    $form['loqate']['address_columns'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Address columns'),
      '#description' => $this->t('Specify which address columns to include in the export.'),
      '#options' => [
        PcaAddressElement::LINE1 => $this->t('Line 1'),
        PcaAddressElement::LINE2 => $this->t('Line 2'),
        PcaAddressElement::LOCALITY => $this->t('Locality'),
        PcaAddressElement::ADMINISTRATIVE_AREA => $this->t('Administrative area'),
        PcaAddressElement::POSTAL_CODE => $this->t('Post code'),
        PcaAddressElement::COUNTRY_CODE => $this->t('Country'),
        PcaAddressElement::PAF => $this->t('PAF validated'),
      ],
      '#default_value' => $export_options['address_columns'],
    ];
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
      if (!in_array($composite_key, $options['address_columns'])) {
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
      if (!in_array($composite_key, $export_options['address_columns'])) {
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
