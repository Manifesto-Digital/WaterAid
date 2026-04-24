<?php

namespace Drupal\wateraid_donation_sf3ds\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\wateraid_donation_forms\DonationConstants;
use Drupal\wateraid_donation_forms\Element\DonationsWebformAmount;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Donations Webform Handler.
 *
 * @package Drupal\wateraid_donation_sf3ds\Plugin\WebformHandler
 *
 * @WebformHandler(
 *   id = "sf3ds",
 *   label = @Translation("Sf 3ds"),
 *   category = @Translation("SF 3ds"),
 *   description = @Translation("SF 3ds"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class Sf3dsWebformHandler extends WebformHandlerBase {

  /**
   * Webform handler for Sf3ds payments.
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

    $amount = $form_state->get(DonationsWebformAmount::STORAGE_AMOUNT) ?? $this->request->get('val');
    $payment_frequency = $form_state->get(DonationsWebformAmount::STORAGE_FREQUENCY) ?? $this->request->get('fq');
    $currency = $this->getCurrency();

    $prefix = DonationConstants::DONATION_PREFIX;

    // Extract generic payment data values.
    $payment_data = [];
    $payment_data[$prefix . 'amount'] = $amount;
    $payment_data[$prefix . 'currency'] = $currency;
    $payment_data[$prefix . 'frequency'] = $payment_frequency;
    $webform_submission->setData(array_merge($webform_submission->getData(), $payment_data));
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission): void {

    // Confirm form for sf3ds is non-webform form POSTing direct to salesforce.
    if ('sf3ds' == trim($webform_submission->getData()['payment']['payment_methods'] ?? '')) {
      $input = $form_state->getUserInput();

      if ($input['sf3ds_card_form']['PtToken']) {

        /** @var \Drupal\wateraid_donation_sf3ds\Service\Sf3dsService $sf3ds */
        $sf3ds = \Drupal::service('wateraid_donation_sf3ds');
        $url = $sf3ds->getFormAction();

        $data = $webform_submission->getData();
        $input = $form_state->getUserInput();

        $post = [];

        $map = [
          'FirstNm' => 'first_name',
          'LastNm' => 'last_name',
          'FirstKnNm' => 'first_name_in_japanese',
          'LastKnNm' => 'last_name_in_japanese',
          'PostCd' => 'postcode',
          'State' => 'prefecture',
          'City' => 'city',
          'Street' => 'street',
          'Phone' => 'phone',
          'IndCorp' => 'individual_corporate',
          'CorpName' => 'corporate_name',
          'CompanyKnNm' => 'corporate_name_in_japanese',
          'Receipt' => 'receipt',
          'Memo' => 'memo',
          'Agree' => 'tong_yi_suru',
          'EmailMaga' => 'e_newsletter',
        ];
        foreach ($map as $sf => $wf) {
          $post[$sf] = $data[$wf];
        }

        $post['Amount'] = $data['donation_amount']['amount'];
        $post['PtToken'] = $input['sf3ds_card_form']['PtToken'];
        $post['AccTyp'] = 1;
        $post['PtTyp'] = 1;
        $post['PtWay'] = 1;
        $post['SuccessURL'] =  Url::fromRoute(
          'wateraid_donation_sf3ds.success',
          ['token' => $webform_submission->getToken()],
          ['absolute' => TRUE]
        )->toString();
        $post['FailURL'] = $this->getWebform()->toUrl();

        $one = 1;

        try {
          $response = \Drupal::httpClient()->post($url, [
            'headers' => [
              'Content-type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => $post,
          ]);

          if ($response->getStatusCode() === 200) {

            // Otherwise revert to default confirmation behavior.
            parent::confirmForm($form, $form_state, $webform_submission);
          }
        }
        catch (\Exception $e) {
          $this->getLogger('wateraid_donation_sf3ds')->error($this->t('Error posting data to salesforce: :error', [
            ':error' => $e->getMessage(),
          ]));
        }
      }

      // If we reach this point, something has gone wrong somewhere.
      $this->messenger()->addError($this->t('There was a problem taking your donation. Please contact us for assistance.'));
      $form_state->setRedirectUrl($this->getWebform()->toUrl());
    }
    else {

      // Otherwise revert to default confirmation behavior.
      parent::confirmForm($form, $form_state, $webform_submission);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission): void {
    parent::alterForm($form, $form_state, $webform_submission);

    if ($form['actions']['submit']['#attributes']['class']) {
      $form['actions']['submit']['#attributes']['class'][] = 'sf3ds_submit';
    }
  }

  /**
   * Get the currency that applies to this webform.
   *
   * @return string
   *   3 character currency code.
   */
  private function getCurrency(): string {
    return $this->getWebform()->getThirdPartySetting('wateraid_donation_forms', 'currency', 'GBP');
  }

}
