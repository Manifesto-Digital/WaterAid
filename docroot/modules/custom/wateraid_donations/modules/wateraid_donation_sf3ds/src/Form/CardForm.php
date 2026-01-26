<?php

namespace Drupal\wateraid_donation_sf3ds\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\wateraid_donation_sf3ds\Service\Sf3dsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sf3ds card details form.
 */
class CardForm extends FormBase {

  /**
   * The 'wateraid_donation_sf3ds' service.
   *
   * @var \Drupal\wateraid_donation_sf3ds\Service\Sf3dsService
   */
  private Sf3dsService $sf3dsService;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @return CardForm
   *   The CardForm.
   */
  public static function create(ContainerInterface $container) {
    $static = parent::create($container);
    $static->sf3dsService = $container->get('wateraid_donation_sf3ds');
    return $static;
  }

  /**
   * Access handler.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result decision.
   */
  public static function access(): AccessResultInterface {
    /** @var \Drupal\wateraid_donation_sf3ds\Service\Sf3dsService $sf3ds */
    $sf3ds = \Drupal::service('wateraid_donation_sf3ds');

    // Only allow access if a webform submission exists.
    if ($webform_submission = $sf3ds->getWebformSubmissionFromRoute()) {

      // Access no longer allowed after first success flagged.
      if ($webform_submission->getData()['payment']['payment_response']) {
        return AccessResult::forbidden();
      }

      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'sf3ds-card-form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    if ($webform_submission = $this->getSf3dsService()->getWebformSubmissionFromRoute()) {

      $form['#attached']['library'][] = 'wateraid_donation_sf3ds/form.card';
      $form['#action'] = $this->getSf3dsService()->getFormAction();

      $map = [
        'FirstNm' => 'first_name',
        'LastNm'  => 'last_name',
        'FirstNmJp' => 'first_name_in_japanese',
        'LastNmJp' => 'last_name_in_japanese',
        'PostCode' => 'postcode',
        'Prefecture' => 'prefecture',
        'City' => 'city',
        'Street' => 'street',
        'Phone' => 'phone',
        'Amount' => 'donation__amount',
        'IndCorp' => 'individual_corporate',
        'CorpName' => 'corporate_name',
        'CorpNameJp' => 'corporate_name_in_japanese',
        'Receipt' => 'receipt',
        'Memo' => 'memo',
        'Agree' => 'agree',
        'ENewsletter' => 'e_newsletter',
      ];
      foreach ($map as $sf => $wf) {
        $form[$sf] = [
          '#type' => 'hidden',
          '#value' => $webform_submission->getData()[$wf] ?? '',
          '#attributes' => [
            'data-submission-value' => strtolower($sf),
          ],
        ];
      }
      $form['Email'] = [
        '#type' => 'hidden',
        '#value' => $webform_submission->getData()['email']['email'] ?? '',
        '#attributes' => [
          'data-submission-value' => 'email',
        ],
      ];
      $form['AccTyp'] = ['#type' => 'hidden', '#value' => 1];
      $form['PtTyp'] = ['#type' => 'hidden', '#value' => 1];
      $form['PtWay'] = ['#type' => 'hidden', '#value' => 1];
      $form['Token'] = ['#type' => 'hidden', '#value' => '1330593726'];
      $form['SuccessUrl'] = [
        '#type' => 'hidden',
        '#value' => Url::fromRoute(
        'wateraid_donation_sf3ds.success',
          ['token' => $webform_submission->getToken()],
          ['absolute' => TRUE]
        )->toString(),
      ];
      $form['FailUrl'] = [
        '#type' => 'hidden',
        '#value' => Url::fromRoute(
          'wateraid_donation_sf3ds.cardForm',
          ['token' => $webform_submission->getToken()],
          ['absolute' => TRUE]
        )->toString(),
      ];

      $form['cardholder'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Card holders name'),
        '#attributes' => ['class' => ['clear-on-submit']],
      ];

      $form['cardnumber'] = [
        '#type' => 'number',
        '#required' => TRUE,
        '#title' => $this->t('Card number'),
        '#attributes' => [
          'data-rule-minlength' => 10,
          'data-rule-maxlength' => 16,
          'data-validate-max-length' => 16,
          'class' => [
            'clear-on-submit',
          ],
        ],
      ];

      $form['expiry_csv'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['inline-desktop-fields', 'inline-desktop-fields--60-40']],
      ];

      $form['expiry_csv']['expiration'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Expiry date'),
        '#attributes' => [
          'class' => [
            'fieldset-without-border',
            'fieldset-inline-elements',
            'fieldset-show-legend', 'fieldset-hidden-field-labels',
          ],
        ],
        'month' => [
          '#required' => TRUE,
          '#type' => 'number',
          '#title' => $this->t('Expiry date month'),
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
          '#required' => TRUE,
          '#type' => 'number',
          '#title' => $this->t('Expiry date Year'),
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

      $form['expiry_csv']['security_code'] = [
        '#type' => 'number',
        '#required' => TRUE,
        '#title' => $this->t('Security code'),
        '#attributes' => [
          'data-rule-minlength' => 3,
          'data-rule-maxlength' => 4,
          'data-validate-max-length' => 4,
          'class' => [
            'clear-on-submit',
          ],
        ],
      ];

      $form['PtToken'] = [
        '#type' => 'hidden',
        '#attributes' => ['id' => ['edit-pttoken']],
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Make payment'),
      ];
    }
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // NOT USED, this form has it action changed, so is never submitted back
    // to Drupal.
  }

  /**
   * Get 'wateraid_donation_sf3ds' service.
   *
   * @return \Drupal\wateraid_donation_sf3ds\Service\Sf3dsService
   *   The 'wateraid_donation_sf3ds' service.
   */
  public function getSf3dsService(): Sf3dsService {
    return $this->sf3dsService;
  }

}
