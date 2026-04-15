<?php

namespace Drupal\wateraid_donation_sf3ds\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a webform element for a Stripe single card element.
 *
 * @FormElement("sf3ds_card_form")
 */
class Sf3DsCardForm extends WebformCompositeBase {

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

    $element['#attached']['library'][] = 'wateraid_donation_sf3ds/form.card';

    $element['AccTyp'] = ['#type' => 'hidden', '#value' => 1];
    $element['PtTyp'] = ['#type' => 'hidden', '#value' => 1];
    $element['PtWay'] = ['#type' => 'hidden', '#value' => 1];
    $element['Token'] = ['#type' => 'hidden', '#value' => '1330593726'];

    $element['cardnumber'] = [
      '#type' => 'number',
      '#title' => t('Card number'),
      '#attributes' => [
        'data-rule-minlength' => 10,
        'data-rule-maxlength' => 16,
        'data-validate-max-length' => 16,
        'class' => [
          'clear-on-submit',
        ],
      ],
    ];

    $element['cardholder'] = [
      '#type' => 'textfield',
      '#title' => t('Card holders name'),
      '#attributes' => ['class' => ['clear-on-submit']],
    ];

    $element['expiry_csv'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['inline-desktop-fields', 'inline-desktop-fields--60-40']],
    ];

    $element['expiry_csv']['expiration'] = [
      '#type' => 'fieldset',
      '#title' => t('Expiry date'),
      '#attributes' => [
        'class' => [
          'fieldset-without-border',
          'fieldset-inline-elements',
          'fieldset-show-legend', 'fieldset-hidden-field-labels',
        ],
      ],
      'month' => [
        '#type' => 'number',
        '#title' => t('Expiry date month'),
        '#suffix' => '<span class="inline-seperator">/</span>',
        '#min' => 1,
        '#max' => 12,
        '#step' => 1,
        '#attributes' => [
          'placeholder' => 'MM',
          'data-validate-max-length' => 2,
          'class' => [
            'clear-on-submit',
          ],
        ],
      ],
      'year' => [
        '#type' => 'number',
        '#title' => t('Expiry date Year'),
        '#min' => date('Y'),
        '#max' => (int) date('Y') + 20,
        '#step' => 1,
        '#attributes' => [
          'placeholder' => 'YYYY',
          'data-validate-max-length' => 4,
          'class' => [
            'clear-on-submit',
          ],
        ],
      ],
    ];

    $element['expiry_csv']['security_code'] = [
      '#type' => 'number',
      '#title' => t('Security code'),
      '#attributes' => [
        'data-rule-minlength' => 3,
        'data-rule-maxlength' => 4,
        'data-validate-max-length' => 4,
        'class' => [
          'clear-on-submit',
        ],
      ],
    ];

    $element['PtToken'] = [
      '#type' => 'hidden',
      '#attributes' => ['id' => ['edit-pttoken']],
    ];

    $element['submit'] = [
      '#type' => 'submit',
      '#value' => t('Make payment'),
      '#button_type' => 'webform-submit',
      '#attributes' => [
        'class' => [
          'sf3ds_submit',
        ],
      ],
    ];

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
    if ($submission = $object->getEntity()) {
      $element['SuccessUrl'] = [
        '#type' => 'hidden',
        '#value' => Url::fromRoute(
          'wateraid_donation_sf3ds.success',
          ['token' => $submission->getToken()],
          ['absolute' => TRUE]
        )->toString(),
      ];
    }

    // Hide the card form if not a card payment.
    if (isset($complete_form['elements']['other']['payment_information']['payment']['payment_methods']['one_off']['#parents'])) {
      $name = wateraid_content_listing_get_element_name($complete_form['elements']['other']['payment_information']['payment']['payment_methods']['one_off']['#parents'], 'selection');

      foreach (Element::children($element) as $child) {
        if (isset($element[$child])) {
          $element[$child]['#states'] = [
            'visible' => [
              ':input[name="' . $name . '"]' => ['value' => 'sf3ds'],
            ],
          ];

          if (in_array($child, ['cardnumber', 'cardholder', 'expiry_csv'])) {
            if ($child == 'expiry_csv') {
              $element[$child]['expiration']['month']['#states'] = [
                'required' => [
                  ':input[name="' . $name . '"]' => ['value' => 'sf3ds'],
                ],
              ];
              $element[$child]['expiration']['year']['#states'] = [
                'required' => [
                  ':input[name="' . $name . '"]' => ['value' => 'sf3ds'],
                ],
              ];
              $element[$child]['security_code']['#states'] = [
                'required' => [
                  ':input[name="' . $name . '"]' => ['value' => 'sf3ds'],
                ],
              ];
            }
            else {
              $element[$child]['#states'] = [
                'required' => [
                  ':input[name="' . $name . '"]' => ['value' => 'sf3ds'],
                ],
              ];
            }
          }
        }
      }
    }

    return $element;
  }


  /**
   * Validates a composite element.
   */
  public static function validateWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): void {
    parent::validateWebformComposite($element, $form_state, $complete_form);

    $values = $form_state->cleanValues()->getValues();

    if (isset($values['payment']['payment_methods']) && $values['payment']['payment_methods'] !== 'gmo_bank_transfer') {
      foreach (NestedArray::getValue($values, $element['#parents']) as $key => $value) {
        if (in_array($key, [
          'cardnumber',
          'cardholder',
          'month',
          'year',
          'security_code',
        ])) {
          if (empty($value)) {
            if (isset($element[$key]['#title'])) {
              $label = $element[$key]['#title']->__toString();
              $component = &$element[$key];
            }
            elseif (isset($element['expiry_csv']['expiration'][$key]['#title'])) {
              $label = $element['expiry_csv']['expiration'][$key]['#title']->__toString();
              $component = &$element['expiry_csv']['expiration'][$key];
            }
            elseif (isset($element['expiry_csv'][$key]['#title'])) {
              $label = $element['expiry_csv'][$key]['#title']->__toString();
              $component = &$element['expiry_csv'][$key];
            }
            else {
              $label = ucwords($key);
              $component = &$element;
            }
            $form_state->setError($component, t('The :field is required', [
              ':field' => $label,
            ]));
          }
        }
      }
    }
  }

}
