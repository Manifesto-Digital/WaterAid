<?php

namespace Drupal\loqate_email\Plugin\WebformElement;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\loqate_email\Element\LoqateEmailCompositeElement;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides WebformCompositeExample webform composite element.
 *
 * @WebformElement(
 *   id = "loqate_email_composite",
 *   label = @Translation("Loqate email"),
 *   description = @Translation("Provides a loqate email composite element."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class LoqateEmailComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultValidationOptions(): array {
    return [
      'loqate_validation' => TRUE,
      'loqate_validation_refuse_disposable' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties(): array {
    return $this->getDefaultValidationOptions()
      + parent::defineDefaultProperties()
      + $this->defineDefaultMultipleProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['validation']['loqate_validation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loqate'),
      '#description' => $this->t('Validate the email address using Loqate.'),
      '#return_value' => TRUE,
      '#access' => TRUE,
    ];

    $form['validation']['loqate_validation_refuse_disposable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Refuse disposable or temporary email addresses'),
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

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements(): array {
    return LoqateEmailCompositeElement::getCompositeElements([]);
  }

  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): array {
    $form_state = new FormState();
    $form_completed = [];

    return LoqateEmailCompositeElement::processWebformComposite($element, $form_state, $form_completed);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {
    $lines = [];

    if (!empty($value['title'])) {
      $lines['title'] = $value['title'];
    }

    if (!empty($value['name'])) {
      $lines['name'] = $value['name'];
    }

    return $lines;
  }

}
