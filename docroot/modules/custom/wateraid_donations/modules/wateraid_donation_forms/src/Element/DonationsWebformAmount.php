<?php

namespace Drupal\wateraid_donation_forms\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\wateraid_donation_forms\DisplayModeButtonsTrait;
use Drupal\webform\Element\WebformCompositeBase;
use Drupal\webform\Element\WebformOtherBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a webform element for a donations amount element.
 *
 * @FormElement("donations_webform_amount")
 */
class DonationsWebformAmount extends WebformCompositeBase {

  use DisplayModeButtonsTrait;

  /**
   * The storage name for "frequency" recording.
   */
  public const STORAGE_FREQUENCY = 'frequency';

  /**
   * The storage name for "amount" recording.
   */
  public const STORAGE_AMOUNT = 'amount';

  /**
   * The storage name for "duration" recording.
   */
  public const STORAGE_DURATION = 'duration';

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

    $elements['impact_statistics'] = [
      '#title' => t('Impact Statistics'),
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'wa-donations--impact-statistics',
          'impact-statistics',
        ],
      ],
      '#weight' => -2,
    ];

    $elements['frequency'] = [
      '#type' => 'donations_webform_buttons',
      '#title' => t('Frequency'),
      '#attributes' => [
        'class' => [
          'wa_donations_frequency',
        ],
      ],
      '#options' => [],
      '#weight' => -1,
    ];

    $elements['amount'] = [
      '#type' => 'container',
      '#title' => t('Amount'),
      '#attributes' => [
        'class' => [
          'wa_donations_amounts',
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderWebformCompositeFormElement($element) {
    $element = parent::preRenderWebformCompositeFormElement($element);
    $form_elements = [
      'amount',
      'frequency',
    ];
    foreach ($form_elements as $form_element) {
      $key = '#' . $form_element . '__display_mode';
      $element[$form_element]['#attributes']['class'][] = self::getDisplayModeClassByElement($element, $key);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): array {
    parent::processWebformComposite($element, $form_state, $complete_form);

    /** @var \Drupal\webform_wizard_single_page\WebformWizardSinglePageSubmissionForm $object */
    $object = $form_state->getFormObject();

    /** @var \Drupal\webform\WebformSubmissionInterface|null $submission */
    $submission = $object->getEntity();

    if (isset($webform) || !empty($element['#webform']) && $webform = Webform::load($element['#webform'])) {
      // Take the "storage" values into account, because we can skip the first
      // step with the WebformWizardExtraSubmissionForm class logic, which
      // means that the form state won't have the values defined initially.
      $storage_frequency = $form_state->get(self::STORAGE_FREQUENCY) ?: NULL;
      $storage_amount = $form_state->get(self::STORAGE_AMOUNT) ?: NULL;
      $storage_duration = $form_state->get(self::STORAGE_DURATION) ?: NULL;
      /** @var \Drupal\webform\Plugin\WebformHandlerPluginCollection $handlers */
      $handlers = $webform->getHandlers('wateraid_donations');
      if ($handlers->count() > 0) {

        /** @var \Drupal\wateraid_donation_forms\Plugin\WebformHandler\DonationsWebformHandler $handler */
        $handler = $handlers->getIterator()->current();

        // Set the submission so we can check if it is attached to a paragraph.
        $handler->setWebformSubmission($submission);
        $amounts = $handler->getAmounts();
        $amount_defaults_all = $handler->getAmountDefaults();

        $durations = $handler->getDurations();
        $duration_defaults_all = $handler->getDurationDefaults();

        $stats_text = $handler->getImpactStatistics();

        // Set the impact statistics text if available.
        if ($stats_text) {
          $element['impact_statistics']['#markup'] = $stats_text;
        }
        else {
          unset($element['impact_statistics']);
        }

        // Set frequency options by merge overriding (Do not concat arrays!).
        $element['frequency'] = array_merge($element['frequency'], self::getElementProperties($amounts));

        // Retrieve default from storage & fallback to default from config.
        $element['frequency']['#default_value'] = $storage_frequency ?? $amount_defaults_all['frequency_default'];
        $element['frequency']['#new_frequencies'] = $handler->getNewFrequencies();

        // If value already set, then need to preserve in replacement elements.
        if (!empty($element['#value']['frequency'])) {
          $element['frequency']['#default_value'] = $element['#value']['frequency'];
        }

        // Hide frequency selection if one or no options.
        if (count($element['frequency']['#options']) <= 1) {
          // Add class to display label while frequency option is one.
          $element['frequency']['#attributes']['class'][] = 'single-frequency-label';
        }

        $element['frequency']['#after_build'][] = [self::class, 'afterBuildFrequency'];

        $element['#element_validate'] = [
          [
            get_called_class(), 'validateDonationsWebformAmount',
          ],
        ];

        // Process amount element into container with amount selection for each
        // frequency type.
        $element['amount']['#type'] = 'container';
        $element['amount']['#value'] = '';

        $form_has_amounts = FALSE;

        foreach ($amounts as $type_key => $type_details) {

          $frequency_has_amounts = FALSE;
          $amount_defaults = $amount_defaults_all[$type_key];

          // Wrap amount selector element in container - theme_wrappers did
          // not work.
          $element['amount'][$type_key] = [
            '#type' => 'container',
            '#value' => '',
            '#attributes' => [
              'class' => [
                'wa_donation_amounts-' . $type_key,
                'wa_donation_amounts_container',
              ],
              'style' => 'display: none',
            ],
          ];

          // Support pre-defined amounts.
          if (!empty($type_details['amounts'])) {
            // Add an amount selection element for each frequency - use JS to
            // switch between them.
            $element['amount'][$type_key]['amounts'] = [
              '#type' => 'webform_buttons',
              '#title' => $element['amount']['#title'],
              '#default_value' => $amount_defaults['default_amount'],
              '#after_build' => [
                [self::class, 'afterBuild'],
              ],
            ] + self::getElementProperties($type_details['amounts'], 'benefit');

            $form_has_amounts = TRUE;
            $frequency_has_amounts = TRUE;
          }
          else {
            // Add class to display label while amount option is one.
            $element['amount'][$type_key]['#attributes']['class'][] = 'single-amount-label';
          }

          // Support other amount field.
          if (!empty($type_details['allow_other_amount'])) {
            // Use own other amount element to sort out validation issues.
            $element['amount'][$type_key]['amounts']['#type'] = 'donations_amount_webform_buttons_other';

            // Set other option as default if no amount options are specified.
            if ($frequency_has_amounts !== TRUE) {
              $element['amount'][$type_key]['amounts']['#default_value'] = WebformOtherBase::OTHER_OPTION;
            }

            // Use own text field to get input mask and min validation.
            $element['amount'][$type_key]['amounts']['#other__type'] = 'donations_webform_amount_textfield';

            // Use amount sub-element placeholder.
            $element['amount'][$type_key]['amounts']['#other__placeholder'] = !empty($element['#amount__placeholder']) ? $element['#amount__placeholder'] : t('Enter amount');

            // Set input mask - see https://github.com/RobinHerbots/Inputmask.
            // Input mask moved to wateraid_donations_form.element.amount.js.
            if (!empty($type_details['minimum_amount'])) {
              $element['amount'][$type_key]['amounts']['#other__min'] = $type_details['minimum_amount'];
            }
          }

          // If value already set, then need to preserve in replacement
          // elements.
          if ($element['#value']['frequency'] === $type_key) {
            // Check existing value.
            if (isset($element['#value']['amount'][$type_key])) {
              // Check if the value is one of the pre-defined set.
              $element['amount'][$type_key]['amounts']['#default_value'] = $element['#value']['amount'][$type_key]['amounts']['buttons'];
            }
            // Fallback to default from different structure after
            // normalisation.
            else {
              $element['amount'][$type_key]['amounts']['#default_value'] = $element['#value']['amount'];
            }
          }
          // Retrieve default from storage.
          elseif ($storage_frequency === $type_key && $storage_amount !== NULL) {
            $element['amount'][$type_key]['amounts']['#default_value'] = $storage_amount;
          }
          // Fallback to default from config.
          else {
            $element['amount'][$type_key]['amounts']['#default_value'] = $amount_defaults['default_amount'];
          }

        }

        if ($form_has_amounts === TRUE) {
          // Don't hide the default options.
          $default_type_key = $element['frequency']['#default_value'];
          unset($element['amount'][$default_type_key]['#attributes']['style']);

          // Add a benefit message element.
          $element['message'] = [
            '#type' => 'webform_markup',
            '#theme_wrappers' => [
              'container' => [
                '#attributes' => ['class' => 'wa_donations_benefit'],
              ],
            ],
            '#markup' => '',
            '#weight' => -1,
          ];
        }

        // ===== DURATIONS =====
        // Process duration element into container with duration selection for
        // each frequency type.
        $element['duration']['#type'] = 'container';
        $element['duration']['#value'] = '';

        foreach ($durations as $type_key => $type_details) {

          $duration_defaults = $duration_defaults_all[$type_key];

          // Wrap amount selector element in container - theme_wrappers did
          // not work.
          $element['duration'][$type_key] = [
            '#type' => 'container',
            '#value' => '',
            '#attributes' => [
              'class' => [
                'wa_donation_amounts-' . $type_key,
                'wa_donation_durations-' . $type_key,
                'wa_donation_amounts_container',
              ],
              'style' => 'display: none',
            ],
          ];

          // Support pre-defined amounts.
          if (!empty($type_details['durations'])) {

            $properties = self::getElementProperties($type_details['durations'], 'benefit');

            // Add a duration selection element for each frequency - use JS to
            // switch between them.
            $element['duration'][$type_key]['durations'] = [
              '#type' => 'webform_buttons',
              '#title' => t('Please select duration'),
              '#default_value' => $duration_defaults['default_duration'],
              '#after_build' => [
                [self::class, 'afterBuild'],
              ],
              '#access' => count($properties['#options']) > 1,
            ] + $properties;

            if (count($properties['#options']) < 2) {
              $months = array_keys($properties['#options'])[0];
              $element['duration'][$type_key]['durations_text'] = [
                '#markup' => t('This is a fixed period donation lasting @months month(s).', ['@months' => $months]),
              ];
              $element['duration'][$type_key]['durations']['#value'] = $months;
            }

          }
          else {
            // Add class to display label while amount option is one.
            $element['duration'][$type_key]['#attributes']['class'][] = 'single-duration-label';
          }

          // If value already set, then need to preserve in replacement
          // elements.
          if ($element['#value']['frequency'] === $type_key) {
            // Check existing value.
            if (isset($element['#value']['duration'][$type_key])) {
              // Check if the value is one of the pre-defined set.
              $element['duration'][$type_key]['durations']['#default_value'] = $element['#value']['duration'][$type_key]['durations'];
            }
            // Fallback to default from different structure after
            // normalisation.
            else {
              $element['duration'][$type_key]['durations']['#default_value'] = $element['#value']['duration'];
            }
          }
          // Retrieve default from storage.
          elseif ($storage_frequency === $type_key && $storage_duration !== NULL) {
            $element['duration'][$type_key]['durations']['#default_value'] = $storage_duration;
          }
          // Fallback to default from config.
          else {
            $element['duration'][$type_key]['durations']['#default_value'] = $duration_defaults['default_duration'];
          }

        }
        // ====== END DURATIONS ======
        // Check if the form is using v2.
        $webform_id = $complete_form['#webform_id'];

        /** @var \Drupal\webform\Entity\Webform $webform */
        $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($webform_id);

        // Get the webform style version.
        $style_version = $webform->getThirdPartySetting('wateraid_forms', 'style_version', 'v2');

        if ($style_version == 'v2') {
          $element['#attached']['library'][] = 'wateraid_donation_forms/wateraid_donation_forms.element.amount_benefits.v2';
          $element['#attached']['library'][] = 'wateraid_donation_forms/wateraid_donation_forms.element.amount.v2';
        }

        $element['#attached']['library'][] = 'wateraid_donation_forms/wateraid_donation_forms.element.amount';
      }

      // @todo If else, we should urge the editor to specify a donation amount in the handler.
    }

    return $element;
  }

  /**
   * Processes the element after build.
   *
   * See select list webform element for select list properties.
   * Add the Stripe Price Code if available.
   *
   * @param mixed[] $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed[]
   *   The element array.
   *
   * @see \Drupal\Core\Render\Element\Select
   *
   * @phpcs:disable
   */
  public static function afterBuild(array $element, FormStateInterface $form_state): array {
    if ($element['#type'] === 'webform_buttons') {
      $buttons_element =& $element;
    }
    else {
      $buttons_element =& $element['buttons'];
    }

    foreach (Element::children($buttons_element) as $button_key) {
      if (!empty($element['#button_descriptions'][$button_key])) {
        $buttons_element[$button_key]['#description'] = $element['#button_descriptions'][$button_key];
      }

      if (!empty($element['#stripe_price_codes'][$button_key])) {
        $buttons_element[$button_key]['#attributes']['data-stripe-price'] = $element['#stripe_price_codes'][$button_key];
      }
    }

    return $element;
  }

  /**
   * Processes the element after build.
   *
   * Set any new frequencies to display new marker
   *
   * @param mixed[] $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed[]
   *   The element array.
   *
   * @see \Drupal\Core\Render\Element\Select
   *
   * @phpcs:disable
   */
  public static function afterBuildFrequency(array $element, FormStateInterface $form_state): array {
    if ($element['#type'] === 'donations_webform_buttons') {
      $buttons_element =& $element;
    }
    else {
      $buttons_element =& $element['buttons'];
    }

    foreach (Element::children($buttons_element) as $button_key) {
      if (in_array($button_key, $element['#new_frequencies'])) {
        $buttons_element[$button_key]['#attributes']['class'][] = 'has-new-frequency';
      }
    }

    return $element;
  }

  /**
   * Get element properties from a settings array.
   *
   * @param mixed[] $settings
   *   Settings array.
   * @param string $description_key
   *   Key.
   *
   * @return mixed[]
   *   Return element properties array.
   */
  private static function getElementProperties(array $settings, string $description_key = ''): array {
    $options = [];
    $descriptions = [];
    $stripe_price_codes = [];

    if (!empty($settings)) {
      foreach ($settings as $key => $details) {
        $options[$key] = $details['label'];

        if ($description_key) {
          $descriptions[$key] = !empty($details[$description_key]) ? $details[$description_key] : '';
        }

        if (isset($details['stripePriceCode'])) {
          $stripe_price_codes[$key] = $details['stripePriceCode'];
        }
      }
    }

    return [
      '#options' => $options,
      '#button_descriptions' => $descriptions,
      '#stripe_price_codes' => $stripe_price_codes,
    ];
  }

  /**
   * Validates a donations_webform_amount element.
   */
  public static function validateDonationsWebformAmount(&$element, FormStateInterface $form_state, &$complete_form) {
    // Donation webform amount field must be converted into a single value.
    $frequency = $element['frequency']['#value'] ?: $element['frequency']['#default_value'];

    $frequency = trim($frequency);

    if ($frequency === 'fixed_period' && !empty($element['duration'][$frequency]['durations']['#parents'])) {
      $duration = NestedArray::getValue($form_state->getValues(), $element['duration'][$frequency]['durations']['#parents']);
      if (!$duration) {
        WebformElementHelper::setRequiredError($element['duration'][$frequency], $form_state, 'Duration');
      }
    }

    $form_state->set(self::STORAGE_FREQUENCY, $frequency);

    if (!empty($element['amount'][$frequency]['amounts']['#parents'])) {
      $amount = NestedArray::getValue($form_state->getValues(), $element['amount'][$frequency]['amounts']['#parents']);
      if (is_array($amount) && isset($amount['buttons'])) {
        $amount = $amount['buttons'];
      }
      $other_amount = $element['amount'][$frequency]['amounts']['other']['#value'];

      $amount = $other_amount ?: $amount;

      $form_state->setValueForElement($element['amount'], $amount);
      $form_state->set(self::STORAGE_AMOUNT, $amount);
    }

    if (!empty($element['duration'][$frequency]['durations']['#parents'])) {
      $duration = NestedArray::getValue($form_state->getValues(), $element['duration'][$frequency]['durations']['#parents']);
      $form_state->setValueForElement($element['duration'], $duration);
      $form_state->set(self::STORAGE_DURATION, $duration);
    }

    return $element;
  }

}
