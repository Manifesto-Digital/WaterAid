<?php

namespace Drupal\wateraid_donation_forms\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformMarkupBase;

/**
 * Provides a 'Payment Logos' element.
 *
 * @WebformElement(
 *   id = "payment_logos",
 *   label = @Translation("Payment logos"),
 *   description = @Translation("Provides a payment logos element."),
 *   category = @Translation("WaterAid Donations"),
 * )
 */
class PaymentLogos extends WebformMarkupBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties(): array {
    return [
      'block_reference' => NULL,
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['form']['block_reference'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Block reference'),
      '#target_type' => 'block_content',
      '#tags' => FALSE,
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['payment_logos'],
      ],
    ];
    return $form;
  }

}
