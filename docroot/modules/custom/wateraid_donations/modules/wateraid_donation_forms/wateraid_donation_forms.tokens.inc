<?php

/**
 * @file
 * Builds placeholder replacement tokens for webforms and submissions.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\wateraid_donation_forms\Donation;
use Drupal\wateraid_donation_forms\DonationConstants;
use Drupal\webform\Entity\Webform;

/**
 * Implements hook_token_info().
 */
function wateraid_donation_forms_token_info() {

  $types = [];
  $tokens = [];

  $types['donation'] = [
    'name' => t('Donation'),
    'description' => t('A plain donation object.'),
    'needs-data' => 'donation',
  ];

  $webform_submission = [];

  $webform_submission['donation-summary'] = [
    'name' => t('WaterAid Donation Summary'),
    'description' => t('List the donation details.'),
  ];

  $webform_submission['donation-amount'] = [
    'name' => t('WaterAid Donation Amount'),
    'description' => t('List the donation details.'),
  ];

  $webform_submission['donation-frequency'] = [
    'name' => t('WaterAid Donation Frequency'),
    'description' => t('The frequency of payment, one off or recurring (direct debit).'),
  ];

  $webform_submission['donation-method'] = [
    'name' => t('WaterAid Donation Payment Method'),
    'description' => t('How the donation is being paid, credit card, paypal, bank transfer..'),
  ];

  $webform_submission['donation-transaction-id'] = [
    'name' => t('WaterAid Donation Transaction ID'),
    'description' => t('List the donation details.'),
  ];

  $webform_submission['donation-date'] = [
    'name' => t('WaterAid Donation date'),
    'description' => t('Either the first payment date for direct debit, or the date of order for one off.'),
  ];

  $webform_submission['donation-fixed-duration-end-date'] = [
    'name' => t('WaterAid fixed duration Donation end date'),
    'description' => t('Todays date in DD/MM/YYYY plus fixed month duration.'),
  ];

  $webform_submission['donation-duration'] = [
    'name' => t('WaterAid fixed duration Donation'),
    'description' => t('Duration of fixed donation.'),
  ];

  $webform_submission['first-name'] = [
    'name' => t('First Name'),
    'description' => t('First name of the donor.'),
  ];

  $webform_submission['gift-aid-confirmation-text'] = [
    'name' => t('Gift aid confirmation text'),
    'description' => t('Gift aid confirmation text.'),
  ];

  $webform_submission['in-memory:name-fallback'] = [
    'name' => t('WaterAid Donation In Memory Name with fallback'),
    'description' => t('Donation In Memory Name with fallback'),
  ];

  $webform_submission['in-memory:title'] = [
    'name' => t('WaterAid Donation In Memory Title'),
    'description' => t('Donation In Memory Title'),
  ];

  $webform_submission['in-memory:first-name'] = [
    'name' => t('WaterAid Donation In Memory First Name'),
    'description' => t('Donation In Memory First Name'),
  ];

  $webform_submission['in-memory:last-name'] = [
    'name' => t('WaterAid Donation In Memory Last Name'),
    'description' => t('Donation In Memory Last Name'),
  ];

  $webform_submission['in-memory:relationship'] = [
    'name' => t('WaterAid Donation In Memory Relationship'),
    'description' => t('Donation In Memory Relationship'),
  ];

  $webform_submission['in-memory:relationship:lowercase'] = [
    'name' => t('WaterAid Donation In Memory Relationship (lowercase)'),
    'description' => t('Donation In Memory Relationship in lowercase'),
  ];

  $node = [];

  $node['webform-is-donate'] = [
    'name' => t('Webform is donate'),
    'description' => t('Returns "donate" if the donation payment handler is added, otherwise nothing.'),
  ];

  $donation = [];

  $donation['currency-sign'] = [
    'name' => t('Donation currency sign'),
  ];

  $donation['amount'] = [
    'name' => t('Donation amount'),
  ];

  $tokens['webform_submission'] = $webform_submission;
  $tokens['node'] = $node;
  $tokens['donation'] = $donation;

  return [
    'types' => $types,
    'tokens' => $tokens,
  ];
}

/**
 * Implements hook_tokens().
 */
function wateraid_donation_forms_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  $replacements = [];

  if ($type === 'webform_submission' && !empty($data['webform_submission'])) {

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $data['webform_submission'];

    $submission_data = $webform_submission->getData();

    /** @var \Drupal\wateraid_donation_forms\DonationServiceInterface $donation_service */
    $donation_service = \Drupal::service('wateraid_donation_forms.donation');

    foreach ($tokens as $name => $original) {
      switch ($name) {

        case 'donation-summary':
          // Add donations summary.
          // Add the extra fields here for the email.
          $build['details'] = [
            '#type' => 'details',
            '#title' => new TranslatableMarkup('Confirmation summary'),
          ];

          if (!empty($submission_data['payment']['frequency'])) {
            if ($submission_data['payment']['frequency'] === 'one_off') {
              $frequency = 'Single payment';
            }
            else {
              $frequency = 'Regular';
            }
            _wateraid_donation_forms_build($build['details'], 'frequency', $frequency, 'Frequency');
          }

          if (!empty($submission_data['payment']['amount'])) {
            $amount = number_format($submission_data['payment']['amount'], 2);
            _wateraid_donation_forms_build($build['details'], 'amount', $amount, 'Amount');
          }

          if (!empty($submission_data['payment']['payment_methods'])) {
            $payment_type = $donation_service->getPaymentProviderLabel($submission_data['payment']['payment_methods']);
            _wateraid_donation_forms_build($build['details'], 'method', $payment_type, 'Payment Method');
          }

          if (!empty($submission_data[DonationConstants::DONATION_PREFIX . 'transaction_id'])) {
            _wateraid_donation_forms_build($build['details'], 'transaction', $submission_data[DonationConstants::DONATION_PREFIX . 'transaction_id'], 'Transaction reference');
          }
          $replacements[$original] = \Drupal::service('renderer')->render($build);
          break;

        case 'donation-amount':

          // Firstly attempt to get amount from payment,
          // secondly attempt to get from field.
          if (
            $amount = $submission_data['payment']['amount'] ?? $submission_data['donation_amount']['amount'] ?? NULL
            and is_numeric($amount)
          ) {
            $replacements[$original] = number_format(trim($amount), 2);
          }
          break;

        case 'donation-frequency':
          if (!empty($submission_data['payment']['frequency'])) {
            if ($submission_data['payment']['frequency'] === 'one_off') {
              $frequency = t('Single payment');
            }
            else {
              $frequency = t('Monthly');
            }
            $replacements[$original] = $frequency;
          }
          break;

        case 'donation-method':
          if (!empty($submission_data['payment']['payment_methods'])) {
            // Add donations summary.
            $payment_type = $donation_service->getPaymentProviderLabel($submission_data['payment']['payment_methods']);
            $replacements[$original] = $payment_type;
          }
          break;

        case 'donation-transaction-id':
          if (!empty($submission_data[DonationConstants::DONATION_PREFIX . 'transaction_id'])) {
            $replacements[$original] = $submission_data[DonationConstants::DONATION_PREFIX . 'transaction_id'];
          }
          break;

        case 'donation-date':
          $replacements[$original] = $submission_data[DonationConstants::DONATION_PREFIX . 'date'] ?? NULL;
          break;

        case 'donation-fixed-duration-end-date':
          if ($end_date = $donation_service->getFixedPeriodDateEnd($webform_submission)) {
            $replacements[$original] = $end_date->format('d/m/Y');
          }
          else {
            $replacements[$original] = t('This donation does not have a fixed period end date');
          }
          break;

        case 'donation-duration':
          $replacements[$original] = $submission_data['donation_amount']['duration'] ?? NULL;
          break;

        case 'first-name':
          $replacements[$original] = $submission_data['contact_name']['first'];
          break;

        case 'gift-aid-confirmation-text':
          // Gift Aid confirmation text.
          $giftaid = $submission_data['gift_aid'];
          if (!empty($giftaid['opt_in']) && $giftaid['opt_in'] === 'Yes') {
            $webform = $webform_submission->getWebform();
            $message_format = $webform->getThirdPartySetting('wateraid_donation_forms', 'gift_aid_email_confirmation')['format'];
            $ga_message = $webform->getThirdPartySetting('wateraid_donation_forms', 'gift_aid_email_confirmation')['value'];
            $replacements[$original] = check_markup(\Drupal::token()
              ->replace($ga_message, ['webform_submission' => $webform_submission]), $message_format);
          }
          else {
            $replacements[$original] = "";
          }
          break;

        /*
         * Create token for In Memory name with fallbacks depending on which
         *   subcomponents have been completed. Order of priority is:
         *   - first-name last-name
         *   - title last-name
         *   - first-name
         *   - "your loved one"
         */
        case 'in-memory:name-fallback':
          // Collect the In Memory data.
          $in_memory_data = $donation_service->getInMemoryData($webform_submission);
          $title = $in_memory_data['in_memory_title'] ?? NULL;
          $first_name = $in_memory_data['in_memory_firstname'] ?? NULL;
          $last_name = $in_memory_data['in_memory_lastname'] ?? NULL;

          // Default to "your loved one".
          $replacements[$original] = new TranslatableMarkup('your loved one');

          // If we have the data for any of the preferred forms use them.
          if (!empty($first_name) && !empty($last_name)) {
            $replacements[$original] = $first_name . ' ' . $last_name;
          }
          elseif (!empty($title) && !empty($last_name)) {
            $replacements[$original] = $title . ' ' . $last_name;
          }
          elseif (!empty($first_name)) {
            $replacements[$original] = $first_name;
          }

          break;

        case 'in-memory:title':
          $in_memory_data = $donation_service->getInMemoryData($webform_submission);
          $replacements[$original] = $in_memory_data['in_memory_title'] ?? NULL;
          break;

        case 'in-memory:first-name':
          $in_memory_data = $donation_service->getInMemoryData($webform_submission);
          $replacements[$original] = $in_memory_data['in_memory_firstname'] ?? NULL;
          break;

        case 'in-memory:last-name':
          $in_memory_data = $donation_service->getInMemoryData($webform_submission);
          $replacements[$original] = $in_memory_data['in_memory_lastname'] ?? NULL;
          break;

        case 'in-memory:relationship':
        case 'in-memory:relationship:lowercase':
          $in_memory_data = $donation_service->getInMemoryData($webform_submission);
          $relationship = $in_memory_data['in_memory_relationship'] ?? NULL;
          if ($relationship && $name === 'in-memory:relationship:lowercase') {
            $relationship = strtolower($relationship);
          }
          $replacements[$original] = $relationship;
          break;
      }
    }

  }
  elseif ($type === 'node' && !empty($data['node'])) {
    /** @var \Drupal\Node\NodeInterface $node */
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'webform-is-donate':
          if ($node->getType() === 'webform') {
            /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
            $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');
            $webform_field_name = $entity_reference_manager->getFieldName($node);
            $webform = $node->$webform_field_name->entity;
            if ($webform instanceof Webform) {
              $handlers = $webform->getHandlers('wateraid_donations');
              if (count($handlers)) {
                $replacements[$original] = t('donate');
              }
            }
          }
          break;
      }
    }
  }
  elseif ($type === 'donation' && !empty($data['donation'])) {

    $donation = $data['donation'];

    if (!$donation || (!$donation instanceof Donation)) {
      return $replacements;
    }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'currency-sign':
          $replacements[$original] = $donation->getCurrencySign() ?? '';
          break;

        case 'amount':
          $replacements[$original] = $donation->getAmount() ?? '';
          break;

        default:
          $replacements[$original] = '';
          break;
      }
    }

  }

  return $replacements;
}

/**
 * Format the field for general response.
 *
 * @param array $build
 *   Build array to add response to.
 * @param string $field
 *   Name of the field to use.
 * @param string $value
 *   Value to use.
 * @param string $title
 *   Title to use for the label.
 */
function _wateraid_donation_forms_build(array &$build, string $field, string $value, string $title): void {

  $build['payment_' . $field] = [
    'label' => [
      '#type' => 'label',
      '#title' => $title,
      '#title_display' => NULL,
      '#attributes' => ['class' => ['payment-' . $field]],
    ],
    $field => [
      '#markup' => $value,
      '#prefix' => '<div class="item-payment-' . $field . '">',
      '#suffix' => '</div>',
    ],
    '#prefix' => '<div class="payment-' . $field . '">',
    '#suffix' => '</div>',
  ];
}
