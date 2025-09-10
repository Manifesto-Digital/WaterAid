<?php

namespace Drupal\loqate\Element;

use Baikho\Loqate\Address\Find;
use Baikho\Loqate\Address\Retrieve;
use CommerceGuys\Addressing\Country\CountryRepository;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\loqate\Loqate;
use Drupal\loqate\PcaAddressElementTrait;
use Drupal\loqate\PcaAddressFieldMapping\PcaAddressElement;
use Drupal\webform\Entity\WebformOptions;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides a simple Loqate PCA address form element.
 *
 * Usage example:
 * @code
 * $form['address'] = [
 *   '#type' => 'pca_address',
 *   '#pca_fields' => [
 *     [
 *       'element' => PcaAddressElement::ADDRESS_LOOKUP,
 *     ],
 *     [
 *       'element' => PcaAddressElement::LINE1,
 *       'field' => PcaAddressField::LINE1,
 *       'mode' => PcaAddressMode::POPULATE,
 *     ],
 *     ...
 *   ],
 *   '#pca_options' => [
 *     'key' => config_key_id, // Defaults to key from config.
 *     'countries' => ['codesList' => 'USA,CAN'],
 *     'setCountryByIP' => false,
 *     ...
 *   ],
 *   '#show_address_fields' => FALSE,
 *   '#allow_manual_input' => TRUE,
 *   ...
 * ];
 * @endcode
 *
 * @FormElement("pca_address_php")
 */
class LoqatePcaAddressPhp extends FormElement {

  private const DEBUG = FALSE;

  protected const SEARCH = 'search';
  protected const SELECT = 'select';
  protected const MANUAL = 'manual';
  protected const CONFIRM = 'confirm';

  use PcaAddressElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $class = get_class($this);
    return $this->buildElementGetInfo() + [
      '#process' => [
        [$class, 'processAddress'],
      ],
      '#after_build' => [
        [$class, 'afterBuildAddress'],
      ],
      '#element_validate' => [
        [$class, 'validateAddress'],
      ],
      '#attached' => [
        'library' => ['loqate/element.pca_address_php.address.js'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Process the address fields.
   *
   * @param mixed[] $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed[] $complete_form
   *   The complete form.
   *
   * @return mixed[]
   *   The updated element.
   */
  public static function processAddress(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    self::preparePcaOptions($element);

    // Ensure tree structure in output.
    $element['#tree'] = TRUE;
    // Ensure ID is different if there are multiple instances on a page.
    $element_id = implode('_', $element['#parents']) . '_address';
    $element['#id'] = $element_id;
    $element['#attributes']['class'][] = 'loqate-address';

    // Include a label to distinguish different elements on a page.
    $element['label'] = [
      '#type' => 'label',
      '#title' => $element['#title'],
    ];

    if (is_array($element['#value'])) {
      $values = &$element['#value'];
    }
    else {
      $values = [];
    }

    $find_options = static::getFindOptions($values, $form_state, $element) ?? ['' => new TranslatableMarkup('- No addresses found. Please enter your address manually. -')];

    $element['address_picker'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Select your address'),
      '#label_attributes' => !empty($element['#loqate_required']) ? ['class' => ['form-required']] : [],
      '#options' => $find_options,
      '#ajax' => [
        'callback' => [static::class, 'ajaxCallback'],
        'wrapper' => $element_id,
      ],
      '#loqate_select' => TRUE,
      '#default_value' => $element['#default_value']['address_picker'] ?? NULL,
    ];

    $element[PcaAddressElement::LINE1] = [
      '#type' => 'textfield',
      '#title' => $element['#' . PcaAddressElement::LINE1 . '_label'] ?? new TranslatableMarkup('Address Line 1'),
      '#default_value' => $element['#default_value'][PcaAddressElement::LINE1] ?? NULL,
    ];

    $element[PcaAddressElement::LINE2] = [
      '#type' => 'textfield',
      '#title' => $element['#' . PcaAddressElement::LINE2 . '_label'] ?? new TranslatableMarkup('Address Line 2'),
      '#default_value' => $element['#default_value'][PcaAddressElement::LINE2] ?? NULL,
    ];

    $element[PcaAddressElement::LOCALITY] = [
      '#type' => 'textfield',
      '#title' => $element['#' . PcaAddressElement::LOCALITY . '_label'] ?? new TranslatableMarkup('City/Town'),
      '#default_value' => $element['#default_value'][PcaAddressElement::LOCALITY] ?? NULL,
      '#size' => 30,
    ];

    $element[PcaAddressElement::ADMINISTRATIVE_AREA] = [
      '#type' => 'textfield',
      '#title' => $element['#' . PcaAddressElement::ADMINISTRATIVE_AREA . '_label'] ?? new TranslatableMarkup('State/Province'),
      '#default_value' => $element['#default_value'][PcaAddressElement::ADMINISTRATIVE_AREA] ?? NULL,
      '#size' => 30,
    ];

    $element[PcaAddressElement::POSTAL_CODE] = [
      '#type' => 'textfield',
      '#title' => $element['#' . PcaAddressElement::POSTAL_CODE . '_label'] ?? new TranslatableMarkup('ZIP/Postal Code'),
      '#default_value' => $element['#default_value'][PcaAddressElement::POSTAL_CODE] ?? NULL,
    ];

    $element[PcaAddressElement::COUNTRY_CODE] = [
      '#type' => 'select',
      '#title' => $element['#' . PcaAddressElement::COUNTRY_CODE . '_label'] ?? new TranslatableMarkup('Country'),
      '#options' => 'country_codes',
      '#default_value' => $element['#default_value'][PcaAddressElement::COUNTRY_CODE] ?? NULL,
    ];

    $country_options = ['' => new TranslatableMarkup('Select your country')];
    if ($country_webform_options = WebformOptions::load($element[PcaAddressElement::COUNTRY_CODE]['#options'])) {
      $country_options += $country_webform_options->getElementOptions($element[PcaAddressElement::COUNTRY_CODE]);
    }
    else {
      $country_repository = new CountryRepository();
      $country_options += $country_repository->getList();
    }
    $element[PcaAddressElement::COUNTRY_CODE]['#options'] = $country_options;

    // Define a hidden field with no access so that the value is not filtered
    // out of the form state.
    $element[PcaAddressElement::PAF] = [
      '#type' => 'hidden',
      '#title' => new TranslatableMarkup('PAF Validated'),
    ];

    $element['find'] = [
      '#type' => 'button',
      '#name' => $element_id . '_find',
      '#value' => new TranslatableMarkup('Find'),
      '#ajax' => [
        'callback' => [static::class, 'ajaxCallback'],
        'wrapper' => $element_id,
      ],
      // Only validate this element.
      '#limit_validation_errors' => [$element['#parents']],
    ];

    $element['current_address'] = [
      '#type' => 'item',
      '#title' => new TranslatableMarkup('Address'),
      '#label_attributes' => ['class' => ['hidden']],
    ];

    $element['manual'] = [
      '#type' => 'button',
      '#value' => $element['#manual_entry_label'] ?? new TranslatableMarkup('Enter address manually'),
      '#name' => $element_id . '_manual',
      '#ajax' => [
        'callback' => [static::class, 'ajaxCallback'],
        'wrapper' => $element_id,
      ],
      // Limit validation errors as they will be entering manually after this.
      '#limit_validation_errors' => [],
      '#loqate_manual' => TRUE,
    ];

    $element['reset'] = [
      '#type' => 'button',
      '#value' => $element['#change_address_label'] ?? new TranslatableMarkup('Change address'),
      '#name' => $element_id . '_change',
      '#ajax' => [
        'callback' => [static::class, 'ajaxCallback'],
        'wrapper' => $element_id,
      ],
      // Limit validation errors as they will be entering manually after this.
      '#limit_validation_errors' => [],
      '#loqate_reset' => TRUE,
    ];

    return $element;
  }

  /**
   * After build method for this element.
   *
   * @param mixed[] $element
   *   The element that has been built.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return mixed[]
   *   The updated element.
   */
  public static function afterBuildAddress(array $element, FormStateInterface $form_state): array {
    if (is_array($element['#value'])) {
      $values = &$element['#value'];
    }
    else {
      $values = [];
    }

    $state = static::currentState($values, $form_state, $element);
    $state_key = ['loqate', 'state'] + $element['#array_parents'];
    $form_state->set($state_key, $state);

    // Use the search labels for Search & Select states  if they have been
    // configured.
    if (in_array($state, [static::SEARCH, static::SELECT])) {
      if (!empty($element['#' . PcaAddressElement::LINE1 . '_search_label'])) {
        $element[PcaAddressElement::LINE1]['#title'] = $element['#' . PcaAddressElement::LINE1 . '_search_label'];
      }
      if (!empty($element['#' . PcaAddressElement::POSTAL_CODE . '_search_label'])) {
        $element[PcaAddressElement::POSTAL_CODE]['#title'] = $element['#' . PcaAddressElement::POSTAL_CODE . '_search_label'];
      }
    }

    // Retrieve the selected address if we are in the CONFIRM state.
    if ($state === static::CONFIRM) {
      $address = static::retrieveSelectedAddress($values, $form_state, $element);
      if (empty($address) && in_array($state, [static::CONFIRM])) {
        $error_message = $element['#lookup_error_message'] ?? new TranslatableMarkup('There was an error retrieving the selected address. Please try again or enter your address manually.');
        $form_state->setError($element['address_picker'], $error_message);
        $state = static::SELECT;
      }
    }

    // If this is the MANUAL state, remove any non-manual values.
    $value_parents = static::getValueParents($form_state);
    if ($state === static::MANUAL) {
      // Unset the PAF validated as manual addresses are never PAF validated.
      $form_state->setValue(array_merge($value_parents, [PcaAddressElement::PAF]), FALSE);
      // Unset address picker values to avoid invalid option selected error.
      $form_state->setValue(array_merge($value_parents, ['address_picker']), NULL);
      $values['address_picker'] = NULL;
      $element['address_picker']['#value'] = NULL;
    }

    // If we are in the SELECT state, get the find options for SELECT state.
    if (in_array($state, [static::SELECT])) {
      $find_options = static::getFindOptions($values, $form_state, $element);
    }

    // Hide various fields based on the current state.
    switch ($state) {
      // Search and SELECT require the same fields to be hidden.
      case static::SEARCH:
      case static::SELECT:
        $element[PcaAddressElement::LINE2]['#wrapper_attributes']['class'][] = 'hidden';
        $element[PcaAddressElement::LOCALITY]['#wrapper_attributes']['class'][] = 'hidden';
        $element[PcaAddressElement::ADMINISTRATIVE_AREA]['#wrapper_attributes']['class'][] = 'hidden';
        $element[PcaAddressElement::COUNTRY_CODE]['#wrapper_attributes']['class'][] = 'hidden';
        break;

      case static::CONFIRM:
        $element[PcaAddressElement::LINE1]['#wrapper_attributes']['class'][] = 'hidden';
        $element[PcaAddressElement::LINE2]['#wrapper_attributes']['class'][] = 'hidden';
        $element[PcaAddressElement::LOCALITY]['#wrapper_attributes']['class'][] = 'hidden';
        $element[PcaAddressElement::ADMINISTRATIVE_AREA]['#wrapper_attributes']['class'][] = 'hidden';
        $element[PcaAddressElement::POSTAL_CODE]['#wrapper_attributes']['class'][] = 'hidden';
        $element[PcaAddressElement::COUNTRY_CODE]['#wrapper_attributes']['class'][] = 'hidden';
        break;

      case static::MANUAL:
        break;
    }
    if (!in_array($state, [static::SELECT]) || empty($find_options)) {
      $element['address_picker']['#wrapper_attributes']['class'][] = 'hidden';
    }

    // Apply required statuses based on element config.
    $required_components = static::getRequiredComponents($element);
    foreach ($required_components as $required_component_id => $required_value) {
      // Only set the component to required if we are currently in the MANUAL
      // state, the whole element is required and the config says this component
      // should be required.
      $element[$required_component_id]['#required'] = in_array($state, [static::MANUAL]) && !empty($element['#loqate_required']) && $required_value;
    }

    // Add the form-required class to POSTAL Code during SEARCH status. This is
    // managed by the custom validate as the component is only required for form
    // submission if the whole element is required but is always required when
    // the Find button is used.
    if ($state === static::SEARCH) {
      $element[PcaAddressElement::POSTAL_CODE]['#label_attributes'] = !empty($element['#loqate_required']) ? ['class' => ['form-required']] : [];
    }

    $element[PcaAddressElement::PAF]['#value'] = $state === static::MANUAL ? FALSE :
     ($element['#default_value'][PcaAddressElement::PAF] ?? FALSE);

    $element['find']['#access'] = in_array($state,
     [static::SEARCH, static::SELECT]);

    $visible_address = [];
    if (!empty($address) && is_array($address)) {
      $visible_address = $address;
      unset($visible_address['address_picker']);
      unset($visible_address[PcaAddressElement::PAF]);
    }
    $element['current_address']['#markup'] = implode(', ', array_filter($visible_address));
    $element['current_address']['#access'] = in_array($state, [static::CONFIRM]);

    // Hide and alter fields for SELECT state. Hidden with CSS so that the
    // values persist by default.
    if (in_array($state, [static::SELECT])) {
      $element[PcaAddressElement::LINE1]['#wrapper_attributes']['class'][] = 'hidden';
      $element[PcaAddressElement::POSTAL_CODE]['#wrapper_attributes']['class'][] = 'hidden';
      $element['find']['#attributes']['class'][] = 'hidden';
      $element['manual']['#attributes']['class'][] = 'link';
    }

    // Switch the visible buttons based on current state.
    if (!in_array($state, [static::SELECT])) {
      $element['manual']['#attributes']['class'][] = 'hidden';
    }
    if (!in_array($state, [static::MANUAL, static::CONFIRM])) {
      $element['reset']['#attributes']['class'][] = 'hidden';
    }

    // If we are in the MANUAL state then the reset label is different.
    if (in_array($state, [static::MANUAL])) {
      $element['reset']['#value'] = $element['#search_input_label'] ?? new TranslatableMarkup('Go back to postcode search');
    }

    return $element;
  }

  /**
   * AJAX callback for doing Loqate address lookup.
   *
   * @param mixed[] $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return mixed[]
   *   The updated address field form array.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state): array {
    $value_parents = static::getValueParents($form_state);
    $form_parents = static::getFormParents($form_state);
    $find_element = NestedArray::getValue($form, $form_parents);

    // If the current value doesn't exist, set it to the first option.
    if (isset($value_parents['address_picker']) && !in_array($value_parents['address_picker'], array_keys($find_element['address_picker']['#options']))) {
      $form_state->setValue(array_merge($value_parents, ['address_picker']), array_key_first($find_element['address_picker']['#options']));
    }

    $find_element['#attached']['library'][] = 'loqate/address-focus';

    return $find_element;
  }

  /**
   * Validates the address.
   *
   * @param mixed[] $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed[] $complete_form
   *   The complete form.
   */
  public static function validateAddress(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    $values = $element['#value'];

    $triggering_element = $form_state->getTriggeringElement();
    // If the form was submitted then we need to validate the element for
    // overall required and missing values.
    if ($triggering_element['#type'] === 'submit') {
      // If the field isn't required don't check for values.
      if (empty($element['#loqate_required'])) {
        return;
      }

      // If the address is PAF validated then the rest is valid.
      if (isset($values['paf']) && $values['paf'] == TRUE) {
        return;
      }

      $state_key = ['loqate', 'state'] + $element['#array_parents'];
      $state = $form_state->get($state_key);
      switch ($state) {
        // If any of the required fields are empty, add an error.
        case static::MANUAL:
          foreach (static::getRequiredComponents($element) as $required_field_id => $required_value) {
            if ($required_value && empty($values[$required_field_id])) {
              $form_state->setError($element[$required_field_id], new TranslatableMarkup('%field is required.', ['%field' => $element[$required_field_id]['#title']]));
            }
          }
          break;

        // Only the postcode field is required during SEARCH.
        case static::SEARCH:
          static::validateRequired(PcaAddressElement::POSTAL_CODE, $element, $form_state);
          break;

        // Only the address picker is required during SELECT.
        case static::SELECT:
          static::validateRequired('address_picker', $element, $form_state);
          break;

        case static::CONFIRM:
          // Nothing is required during CONFIRM.
          break;
      }

      // We don't need to check state or other components as the overall form
      // is being submitted.
      return;
    }

    // Otherwise we need to check the current state to find out which elements
    // need validating.
    $state_key = ['loqate', 'state'] + $element['#array_parents'];
    $state = $form_state->get($state_key);

    switch ($state) {
      case static::SEARCH:
        static::validateRequired(PcaAddressElement::POSTAL_CODE, $element, $form_state);
        break;

      case static::MANUAL:
        // No need to validate manual because #required is set to TRUE.
        break;

      case static::SELECT:
        $find_options = static::getFindOptions($values, $form_state, $element, $state !== static::SELECT);
        if (empty($find_options)) {
          $error_message = $element['#find_error_message'] ?? new TranslatableMarkup("You seem to have entered a postcode that we can't recognise. Please try again e.g. SE13 3AW. If your home is a new build, you may need to enter the full address yourself.");
          $form_state->setError($element, $error_message);
        }

        static::validateAddressPicker($element, $form_state);
        break;

      case static::CONFIRM:
        static::validateAddressPicker($element, $form_state);
        break;
    }
  }

  /**
   * Validate a sub-component of an element.
   *
   * @param string $component_id
   *   The ID of the element to be validated.
   * @param mixed[] $element
   *   The element containing the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  protected static function validateRequired(string $component_id, array $element, FormStateInterface $form_state): void {
    $values = $element['#value'];

    switch ($component_id) {
      case 'address_picker':
        if (empty($values[$component_id])) {
          $form_state->setError($element[$component_id], new TranslatableMarkup('Please select your address from the list or choose %manual_element.', ['%manual_element' => $element['manual']['#value']]));
        }
        break;

      default:
        if (empty($values[$component_id])) {
          $form_state->setError($element[$component_id], new TranslatableMarkup('%field is required to use the address search.', ['%field' => $element[$component_id]['#title']]));
        }
        break;
    }
  }

  /**
   * Validate the address picker component.
   *
   * @param mixed[] $element
   *   The element containing the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  protected static function validateAddressPicker(array $element, FormStateInterface $form_state): void {
    // This validation may be post-select or post-confirm so check the
    // triggering element.
    if ($triggering_element = $form_state->getTriggeringElement()) {
      // If the address picker is the trigger, validate it.
      if (isset($triggering_element['#loqate_select']) && $triggering_element['#loqate_select'] === TRUE) {
        static::validateRequired('address_picker', $element, $form_state);
      }
    }

  }

  /**
   * Get the value array parents.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return mixed[]
   *   An array of array keys for the address values.
   */
  protected static function getValueParents(FormStateInterface $form_state): array {
    $value_parents = [];

    if ($triggering_element = $form_state->getTriggeringElement()) {
      $value_parents = $triggering_element['#parents'];
      array_pop($value_parents);
    }

    return $value_parents;
  }

  /**
   * Get the form parents of the address element.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return mixed[]
   *   An array of form parents for the address element.
   */
  protected static function getFormParents(FormStateInterface $form_state): array {
    $form_parents = [];

    if ($triggering_element = $form_state->getTriggeringElement()) {
      $form_parents = $triggering_element['#array_parents'];
      array_pop($form_parents);
    }

    return $form_parents;
  }

  /**
   * Perform a Find query on the Loqate API to find address options.
   *
   * @param mixed[] $values
   *   The address values to find matches for.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state for storing options to prevent API overuse.
   * @param mixed[] $element
   *   The current form element.
   * @param bool $existing
   *   If TRUE, always return the last find options. Otherwise, return only
   *   return existing options if there is no text to search for.
   *
   * @return string[]
   *   An array of options for a select element.
   */
  protected static function getFindOptions(array $values, FormStateInterface $form_state, array $element, bool $existing = FALSE): array {
    $text = '';
    $text .= $values['address_line1'] ?? '';
    $text .= !empty($values['postal_code']) ? ' ' . $values['postal_code'] : '';

    $form_parents = $element['#parents'];
    $find_key = array_merge(['loqate', 'find_options'], $form_parents, [$text]);
    $existing_find_options = $form_state->get($find_key) ?: [];
    $last_find_key = ['loqate', 'last_options'] + $form_parents;

    $state_key = ['loqate', 'state'] + $element['#array_parents'];
    $previous_state = $form_state->get($state_key) ?: static::SEARCH;

    // Empty text means either a search hasn't been performed or the text fields
    // have been hidden in a later stage.
    // If the previous state was confirm then we are either submitting the whole
    // form or resetting the form and so don't need new options.
    if (empty($text) || $previous_state === static::CONFIRM || $existing) {
      return $form_state->get($last_find_key) ?: [];
    }

    // If there is text and the existing options aren't empty then there's no
    // need to re-query the API.
    if (!empty($existing_find_options)) {
      return $existing_find_options;
    }

    if (self::DEBUG) {
      $options = [
        'GBR|52509479' => 'TEST',
      ];
    }
    else {
      // Get response of postal code.
      $response = self::getAddresses($text);
      $options = [];
      if (!empty($response)) {

        foreach ($response->Items as $address) {
          if (!property_exists($address, 'Type') || $address->Type != 'Address') {
            // Check if type of the item is postcode
            // Then it is a contaner and make another.
            // Request to get addresses in that container.
            if ($address->Type === 'Postcode') {
              $containerResponse = self::getAddresses(NULL, 'container', $address->Id);
              foreach ($containerResponse->Items as $caddress) {
                $options[$caddress->Id] = $caddress->Text . ' ' . $caddress->Description;
              }
            }
            continue;
          }
          $options[$address->Id] = $address->Text . ' ' . $address->Description;
        }
      }
    }

    // If there are options, prepend the none option with instructions.
    if (!empty($options)) {
      $options = ['' => new TranslatableMarkup('- Please select your address -')] + $options;
    }

    $form_state->set($find_key, $options);
    $form_state->set($last_find_key, $options);

    return $options;
  }

  /**
   * Get address from Loqate.
   *
   * @param string|null $text
   *   The search text to find.
   * @param string $request_type
   *   A request type if container or not.
   * @param string $containerID
   *   A container id for the search.
   *
   * @return mixed
   *   The address or NULL.
   */
  public static function getAddresses(?string $text = NULL, string $request_type = 'address', string $containerID = ''): mixed {
    $response = NULL;
    try {
      $api_key = self::getLoqateApiKey();
      if ($api_key) {
        $request = (new Find($api_key));
        if ($request_type === 'address') {
          $request->setText($text);
        }
        else {
          $request->setContainer($containerID);
        }
        $response = $request->makeRequest();
      }
    }
    catch (GuzzleException $e) {
      // Do nothing as this will be caught by empty options handling below.
    }
    // @todo implement response caching to avoid api request.
    return $response;
  }

  /**
   * Retrieve the selected address from Loqate.
   *
   * @param mixed[] $values
   *   An array of form values.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param mixed[] $element
   *   The element the address is selected from.
   *
   * @return string[]|bool
   *   The retrieved address or FALSE on error.
   */
  protected static function retrieveSelectedAddress(array $values, FormStateInterface $form_state, array $element): array|bool {
    $address = FALSE;
    // If no address has been selected from the address picker then do not
    // attempt to retrieve an address.
    if (empty($values['address_picker'])) {
      return FALSE;
    }

    // @todo Check if address has changed since last time before retrieving again.
    $retrieved = $form_state->get('retrieved_addresses') ?: [];

    $address_id = $values['address_picker'];
    // If we have already retrieved this address, return it rather than query
    // the API again.
    if (!empty($retrieved[$address_id])) {
      // Ensure the values are stored in case this is the same address but a
      // different field.
      static::storeRetrievedAddress($values, $retrieved[$address_id], $form_state, $element);
      return $retrieved[$address_id];
    }

    if (self::DEBUG && $address_id === 'GBR|52509479') {
      $address = [
        'address_picker' => $address_id,
        PcaAddressElement::LINE1 => '123',
        PcaAddressElement::LINE2 => 'test',
        PcaAddressElement::LOCALITY => 'locality test',
        PcaAddressElement::ADMINISTRATIVE_AREA => 'admin area',
        PcaAddressElement::POSTAL_CODE => 'T1 1ST',
        PcaAddressElement::COUNTRY_CODE => 'GB',
        PcaAddressElement::PAF => (int) TRUE,
      ];
    }
    else {
      $response = NULL;
      try {
        $api_key = self::getLoqateApiKey();
        if ($api_key) {
          $response = (new Retrieve($api_key))
            ->setId($address_id)
            ->makeRequest();
        }
      }
      catch (GuzzleException $e) {
        // Do nothing with an error as this will be caught by the empty address
        // handling.
      }

      if (!empty($response) && property_exists($response, 'Items') && !empty($response->Items[0])) {
        $address = [
          'address_picker' => $address_id,
          PcaAddressElement::LINE1 => $response->Items[0]->Line1,
          PcaAddressElement::LINE2 => $response->Items[0]->Line2,
          PcaAddressElement::LOCALITY => $response->Items[0]->City,
          PcaAddressElement::ADMINISTRATIVE_AREA => $response->Items[0]->Province,
          PcaAddressElement::POSTAL_CODE => $response->Items[0]->PostalCode,
          PcaAddressElement::COUNTRY_CODE => $response->Items[0]->CountryIso2,
          // All addresses from Loqate are PAF validated.
          PcaAddressElement::PAF => 1,
        ];

        // If the response includes a company name, update the address so that
        // Line 1 is the company name and Lines 1 & 2 are updated accordingly.
        if (!empty($response->Items[0]->Company)) {
          $company_name = $response->Items[0]->Company;

          // If Line 1 isn't empty, rearrange Lines 1 & 2 to keep all info.
          if (!empty($address[PcaAddressElement::LINE1])) {
            // If line 2 is empty, simply move line 1 to 2.
            if (empty($address[PcaAddressElement::LINE2])) {
              $address[PcaAddressElement::LINE2] = $address[PcaAddressElement::LINE1];
            }
            // Otherwise, line 2 isn't empty so prepend it with Line 1.
            else {
              $address[PcaAddressElement::LINE2] = $address[PcaAddressElement::LINE1] . ', ' . $address[PcaAddressElement::LINE2];
            }
          }
          // Always replace Line 1 with the company name.
          $address[PcaAddressElement::LINE1] = $company_name;
        }
      }
    }

    if ($address) {
      static::storeRetrievedAddress($values, $address, $form_state, $element);
    }

    // Add the address to the retrieved array to avoid unnecessary API calls.
    $retrieved[$address_id] = $address;
    $form_state->set('retrieved_addresses', $retrieved);

    return $retrieved[$address_id];
  }

  /**
   * Store a retrieved address in form state user input and values.
   *
   * @param mixed[] $values
   *   The form state values array.
   * @param mixed[] $address
   *   The address to be stored.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param mixed[] $element
   *   The current form element array.
   */
  protected static function storeRetrievedAddress(array &$values, array $address, FormStateInterface $form_state, array $element): void {
    $user_input = $form_state->getUserInput();
    foreach ($address as $address_key => $address_value) {
      $values[$address_key] = $address_value;
      NestedArray::setValue($user_input, array_merge($element['#parents'], [$address_key]), $address_value);
    }

    // Update form state values with the values from the selected address.
    $form_state->setValue($element['#parents'], $values);
    $form_state->setUserInput($user_input);
  }

  /**
   * Retrieve the current state of the element.
   *
   * @param mixed[] $values
   *   The array of values for the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param mixed[] $element
   *   The current element.
   *
   * @return string
   *   The current state of the element.
   *   One of:
   *     - LoqatePcaAddressPhp::SEARCH
   *     - LoqatePcaAddressPhp::SELECT
   *     - LoqatePcaAddressPhp::MANUAL
   *     - LoqatePcaAddressPhp::CONFIRM
   */
  protected static function currentState(array $values, FormStateInterface $form_state, array &$element): string {
    $manual_key = ['loqate', 'manual'] + $element['#array_parents'];

    // If the triggering element was the "Enter address manually" button then
    // we're in the MANUAL state and this should not be overidden by other
    // states so return now.
    if ($triggering_element = $form_state->getTriggeringElement()) {
      if (isset($triggering_element['#loqate_manual']) && $triggering_element['#loqate_manual'] === TRUE && $element['manual']['#name'] === $triggering_element['#name']) {
        $user_input = $form_state->getUserInput();
        // Also set the default country to United Kingdom when triggering manual
        // entry, allowing for WebformOptions values and for CountryRepository
        // values depending on which is available.
        if (!empty($element[PcaAddressElement::COUNTRY_CODE]['#options']['United Kingdom'])) {
          $form_state->setValueForElement($element[PcaAddressElement::COUNTRY_CODE], 'United Kingdom');
          NestedArray::setValue($user_input, array_merge($element['#parents'], [PcaAddressElement::COUNTRY_CODE]), 'United Kingdom');
        }
        elseif (!empty($element[PcaAddressElement::COUNTRY_CODE]['#options']['GB'])) {
          $form_state->setValueForElement($element[PcaAddressElement::COUNTRY_CODE], 'GB');
          NestedArray::setValue($user_input, array_merge($element['#parents'], [PcaAddressElement::COUNTRY_CODE]), 'GB');
        }
        $form_state->setUserInput($user_input);

        $form_state->set($manual_key, TRUE);
        return static::MANUAL;
      }
    }

    if ($triggering_element = $form_state->getTriggeringElement()) {
      // If triggered by the reset button, clear all values and return SEARCH.
      if (isset($triggering_element['#loqate_reset']) && $triggering_element['#loqate_reset'] === TRUE && $element['reset']['#name'] === $triggering_element['#name']) {
        $form_state->setValueForElement($element, NULL);
        $element['#value'] = NULL;
        $components = [
          PcaAddressElement::ORGANIZATION,
          PcaAddressElement::LINE1,
          PcaAddressElement::LINE2,
          PcaAddressElement::LOCALITY,
          PcaAddressElement::ADMINISTRATIVE_AREA,
          PcaAddressElement::DEPENDENT_LOCALITY,
          PcaAddressElement::SORTING_CODE,
          PcaAddressElement::POSTAL_CODE,
          PcaAddressElement::COUNTRY_CODE,
          PcaAddressElement::PAF,
          'address_picker',
        ];
        foreach ($components as $component_name) {
          if (isset($element[$component_name])) {
            $element[$component_name]['#value'] = NULL;
          }
        }

        $form_state->set($manual_key, FALSE);
        return static::SEARCH;
      }
    }

    // If the element has a manual entry and hasn't been reset then it is still
    // manual.
    if ($form_state->get($manual_key) === TRUE) {
      return static::MANUAL;
    }

    // Default state is search.
    $state = static::SEARCH;

    if (isset($element['#value']['paf']) && $element['#value']['paf'] == TRUE) {
      return static::CONFIRM;
    }

    $values = is_array($element['#value']) ? $element['#value'] : [];
    $has_required_search_values = !empty($values[PcaAddressElement::POSTAL_CODE]);

    // Attempt to retrieve a selected address to see whether we should be in the
    // confirm state.
    if (!empty(static::retrieveSelectedAddress($values, $form_state, $element))) {
      $state = static::CONFIRM;
    }
    // Otherwise, if we have search values we're in the SELECT state.
    elseif ($has_required_search_values) {
      $state = static::SELECT;
    }

    return $state;
  }

  /**
   * Get the central Loqate API Key with awareness of current API mode.
   *
   * @return string|null
   *   The API key if found. Otherwise, NULL.
   */
  public static function getLoqateApiKey(): ?string {
    // Get the Loqate api key from configuration.
    $config = \Drupal::configFactory()->get('loqate.loqateapikeyconfig');
    if ($config->get('mode') === 'live') {
      $key_id = $config->get('live_api_key');
    }
    else {
      $key_id = $config->get('test_api_key');
    }

    return Loqate::getApiKey($key_id);
  }

  /**
   * Get the required configuration for each component.
   *
   * @param mixed[] $element
   *   The address element to get configuration from.
   *
   * @return string[]
   *   Associative array of required configuration for the element.
   *   Key is the element id and value is TRUE or FALSE.
   */
  protected static function getRequiredComponents(array $element): array {
    $required = [];

    $fields = [
      PcaAddressElement::LINE1,
      PcaAddressElement::LINE2,
      PcaAddressElement::LOCALITY,
      PcaAddressElement::ADMINISTRATIVE_AREA,
      PcaAddressElement::COUNTRY_CODE,
      PcaAddressElement::POSTAL_CODE,
    ];

    foreach ($fields as $field_name) {
      $required[$field_name] = !empty($element['#' . $field_name . '_required']);
    }

    return $required;
  }

}
