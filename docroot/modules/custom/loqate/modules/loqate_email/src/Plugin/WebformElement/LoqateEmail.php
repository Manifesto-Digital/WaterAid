<?php

namespace Drupal\loqate_email\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\Email;

/**
 * Provides a replacement WebformElement for email.
 *
 * @WebformElement(
 *   id = "email",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Email.php/class/Email",
 *   label = @Translation("Email"),
 *   description = @Translation("Provides a form element for entering an email address."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class LoqateEmail extends Email {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties(): array {
    return [
      'loqate_validation' => FALSE,
      'loqate_validation_refuse_disposable' => TRUE,
    ] + parent::defineDefaultProperties()
      + $this->defineDefaultMultipleProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['validation']['loqate_validation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loqate (deprecated)'),
      '#description' => $this->t('Validate the email address using Loqate.'),
      '#return_value' => TRUE,
      '#access' => TRUE,
    ];

    $form['validation']['loqate_validation_refuse_disposable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Refuse disposable or temporary email addresses (deprecated)'),
      '#description' => $this->t('When checked, display an error if Loqate indicates that the email address is temporary or disposable.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[loqate_validation]"]' => ['checked' => TRUE],
        ],
      ],
      '#return_value' => TRUE,
      '#access' => TRUE,
    ];

    return $form;
  }

}
