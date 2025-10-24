<?php

namespace Drupal\loqate_email\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\loqate_email\Plugin\WebformElement\LoqateEmailComposite;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a CompositeExample form.
 *
 * @FormElement("loqate_email_composite")
 */
class LoqateEmailCompositeElement extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return parent::getInfo() + [
      '#theme_wrappers' => [
        'container',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $element = [];

    $element['email'] = [
      '#type' => 'email',
      '#title' => t('Email address'),
    ];

    $element['hash'] = [
      '#type' => 'hidden',
      '#title' => t('Hash'),
      '#attributes' => [
        'data-hash' => '',
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): void {
    parent::validateWebformComposite($element, $form_state, $complete_form);
    $values = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    if (empty($values['email'])) {
      return;
    }

    $email = $values['email'];
    $hash_value = $values['hash'];

    /** @var \Drupal\loqate_email\ValidatorInterface $validator */
    $validator = \Drupal::service('loqate_email.validator');

    $hash = $validator->getHash($email);

    if (!empty($hash_value) && $hash == $hash_value) {
      // If the hash in form state matches, we know that the front-end
      // validation passed for the email address provided.
      // It is only necessary to check again from the backend if the
      // hash is missing or different.
      return;
    }

    // Check loqate options.
    $options = LoqateEmailCompositeElement::getOptions($element);
    if ($options['loqate_validation'] === FALSE) {
      return;
    }
    $refuse_disposable = $options['loqate_validation_refuse_disposable'];

    $check = $validator->validateEmailAddress($email, $refuse_disposable);
    if ($check['skipped'] === TRUE) {
      if (!empty($check['skipped_message'])) {
        \Drupal::messenger()->addMessage($check['skipped_message']);
      }
    }
    elseif ($check['valid'] === FALSE) {
      // Display an error if the email address is invalid.
      $error_message = $check['invalid_email_error_message'];
      $form_state->setError($element, $error_message);
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderWebformCompositeFormElement($element) {
    parent::preRenderWebformCompositeFormElement($element);

    // Get the error message from config.
    $service = \Drupal::service('loqate_email.validator');
    $error_message = $service->getErrorMessage();

    // Check loqate options.
    $options = LoqateEmailCompositeElement::getOptions($element);
    if ($options['loqate_validation'] === FALSE) {
      return $element;
    }

    $enabled = $options['loqate_validation'];
    $refuse_disposable = $options['loqate_validation_refuse_disposable'];

    $endpoint = Url::fromRoute('loqate_email.validate')->setAbsolute()->toString();

    // Attach settings and library to the element.
    $element['email']['#attributes']['data-loqate'] = TRUE;
    $element['#attached']['drupalSettings']['loqateEmail']['enabled'] = $enabled;
    $element['#attached']['drupalSettings']['loqateEmail']['refuseDisposable'] = $refuse_disposable;
    $element['#attached']['drupalSettings']['loqateEmail']['id'] = $element['#id'];
    $element['#attached']['drupalSettings']['loqateEmail']['endpointUrl'] = $endpoint;
    $element['#attached']['drupalSettings']['loqateEmail']['errorMessage'] = $error_message;
    $element['#attached']['library'][] = 'loqate_email/loqate-validate';

    return $element;

  }

  /**
   * Helper method for determining validation options.
   *
   * @param mixed[] $element
   *   The form element to check.
   *
   * @return string[]
   *   The parsed options.
   */
  public static function getOptions(array $element): array {
    $options_to_check = [
      'loqate_validation_refuse_disposable',
      'loqate_validation',
    ];

    $default_options = LoqateEmailComposite::getDefaultValidationOptions();

    foreach ($options_to_check as $option) {
      if (array_key_exists('#' . $option, $element)) {
        // The array key only exists when set to the non-default state.
        $return[$option] = !$default_options[$option];
      }
      else {
        // Array key is missing so assume default state.
        $return[$option] = $default_options[$option];
      }
    }

    return $return;

  }

}
