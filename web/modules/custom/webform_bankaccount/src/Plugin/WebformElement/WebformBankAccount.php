<?php

namespace Drupal\webform_bankaccount\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_bankaccount\Element\WebformBankAccount as WebformBankAccountElement;

/**
 * Provides a bank account element.
 *
 * @WebformElement(
 *   id = "webform_bankaccount",
 *   label = @Translation("Bank account"),
 *   description = @Translation("Provides a form element to collect bank account details (account, sort code)."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformBankAccount extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements(): array {
    return WebformBankAccountElement::getCompositeElements([]);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array|string {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {
    $lines = [];
    if (!empty($webform_submission['account'])) {
      $lines['account'] = 'Bank account: ' . $webform_submission['account'];
    }
    if (!empty($webform_submission['sort_code'])) {
      $lines['sort_code'] = 'Sort code: ' . $webform_submission['sort_code'];
    }
    return $lines;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['bank_account_validation'] = [
      '#type' => 'details',
      '#title' => $this->t('Bank account validation'),
      '#access' => TRUE,
    ];
    $form['bank_account_validation']['pca_active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate Postcode anywhere validation'),
      '#access' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties(): array {
    $default_properties = parent::getDefaultProperties();
    $default_properties['pca_active'] = FALSE;
    return $default_properties;
  }

}
