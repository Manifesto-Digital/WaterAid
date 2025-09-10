<?php

namespace Drupal\wateraid_donation_forms\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\wateraid_donation_forms\DonationsWebformHandlerTrait;
use Drupal\webform\Element\WebformCompositeBase;
use Drupal\webform\Entity\Webform;

/**
 * Provides a webform element for a Stripe single card element.
 *
 * @FormElement("gift_aid")
 */
class GiftAid extends WebformCompositeBase {

  use DonationsWebformHandlerTrait;

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
    return [
      'opt_in' => [
        '#title' => t('Yes I would like WaterAid to claim Gift Aid on my donation'),
        '#type' => 'checkbox',
      ],
      'date_made' => [
        '#title' => t('Date made'),
        '#type' => 'value',
        '#value' => self::getFormattedDate(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): array {
    parent::processWebformComposite($element, $form_state, $complete_form);

    $values = $form_state->getValue($element['#webform_key']);

    $element['opt_in']['#default_value'] = is_array($values) && $values['opt_in'] === 'Yes' ? 1 : 0;
    $path = \Drupal::service('extension.list.module')->getPath('wateraid_donation_forms');

    $element['gift_aid_logo'] = [
      '#prefix' => '<div class="gift-aid">',
      '#theme' => 'image',
      '#uri' => $path . '/images/gift-aid-logo.png',
      '#alt' => t('Gift aid logo'),
      '#weight' => -2,
      '#attributes' => [
        'class' => [
          'gift-aid-logo',
        ],
      ],
    ];

    $element['gift_aid_text'] = [
      '#type' => 'markup',
      // This text gets overwritten with amount user has entered.
      '#markup' => t('Are you a UK taxpayer? Increase your donation at no extra cost.', [
        ':donation_amount' => 10,
        ':donation_amount_increased' => 12.23,
      ]),
      '#weight' => -1,
    ];

    // Get amount from form storage.
    $amount = $form_state->get('amount');

    if (!empty($amount) && is_numeric($amount)) {

      if (!empty($element['#webform']) && $webform = Webform::load($element['#webform'])) {

        $handler = self::getWebformDonationsHandler($webform);
        // @todo make the calculation configurable.
        $amount_increased = $amount * 1.25;

        // Check the active form styling version.
        $version = $webform->getThirdPartySetting('wateraid_forms', 'style_version', 'v2');
        if ($version == 'v1') {
          $text = "<h1 class='gift-aid-text'>";
          $text .= t('Are you a UK taxpayer? Increase your donation from <span class="amount">:donation_amount</span> donation to <strong class="giftaid-amount">:donation_amount_increased</strong> at no extra cost.', [
            ':donation_amount' => $handler->formatAmount($amount, 'symbol'),
            ':donation_amount_increased' => $handler->formatAmount($amount_increased, 'symbol'),
          ]);
          $text .= "</h1>";
        }
        else {
          // Include extra line-breaks for v2 onwards.
          $text = "<div class='gift-aid-text'>";
          $text .= t('<p>Are you a UK taxpayer?</p><p>Increase your donation from <span class="amount">:donation_amount</span> donation to <strong class="giftaid-amount">:donation_amount_increased</strong> at no extra cost.</p>', [
            ':donation_amount' => $handler->formatAmount($amount, 'symbol'),
            ':donation_amount_increased' => $handler->formatAmount($amount_increased, 'symbol'),
          ]);
          $text .= "</div>";
        }

        $element['gift_aid_text']['#markup'] = $text;
      }
    }

    $element['suffix'] = [
      '#type' => 'markup',
      '#suffix' => '</div>',
    ];

    $element['#element_validate'][] = [get_called_class(), 'validateGiftAidValue'];

    $element['#attached']['library'][] = 'wateraid_donation_forms/wateraid_donation_forms.element.giftaid';

    // Check if the form is using v2.
    $webform_id = $complete_form['#webform_id'];

    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($webform_id);

    // Get the webform style version.
    $style_version = $webform->getThirdPartySetting('wateraid_forms', 'style_version', 'v2');

    if ($style_version == 'v2') {
      $element['#attached']['library'][] = 'wateraid_donation_forms/wateraid_donation_forms.element.giftaid.v2';
    }

    return $element;
  }

  /**
   * Validate callback.
   *
   *  Set the form value correctly (flatten the array).
   *
   * @param mixed[] $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed[] $complete_form
   *   The complete form.
   *
   * @return mixed[]
   *   The updated element.
   */
  public static function validateGiftAidValue(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    $value = $form_state->getValue($element['#name']);
    unset($value['gift_aid_text']);

    if (is_array($value) && $value['opt_in'] == '1') {
      // Force value.
      $value['opt_in'] = 'Yes';
      $value['date_made'] = self::getFormattedDate();
    }

    $form_state->setValueForElement($element, $value);
    return $element;
  }

  /**
   * Call back function to get the formatted time.
   */
  private static function getFormattedDate(): string {
    /** @var \Drupal\Core\Datetime\DateFormatter $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    return $date_formatter->format(time(), 'custom', 'd/m/Y');
  }

}
