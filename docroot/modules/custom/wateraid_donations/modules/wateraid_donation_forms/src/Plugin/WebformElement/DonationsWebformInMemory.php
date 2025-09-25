<?php

namespace Drupal\wateraid_donation_forms\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;

/**
 * Provides a 'donations In Memory' element.
 *
 * @WebformElement(
 *   id = "donations_webform_in_memory",
 *   label = @Translation("Donations In Memory"),
 *   category = @Translation("WaterAid Donations"),
 *   description = @Translation("Provides a form element to accept donation In Memory of a loved one."),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\wateraid_donation_forms\Element\DonationsWebformInMemory
 */
class DonationsWebformInMemory extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties(): array {
    return [
      'in_memory_intro' => '',
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['composite']['in_memory_intro'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('In Memory Intro'),
      '#access' => TRUE,
      '#weight' => -50,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $options): array {
    // Force using key value as export headerm, so we use the more explicit
    // context reference.
    $options['header_format'] = 'key';
    return parent::buildExportHeader($element, $options);
  }

}
