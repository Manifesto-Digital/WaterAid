<?php

/**
 * @file
 * Builds placeholder replacement tokens for webforms and submissions.
 */

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\block_content\Entity\BlockContent;
use Drupal\wateraid_donation_forms\DonationConstants;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Implements hook_token_info().
 */
function webform_bankaccount_token_info(): array {

  $tokens = [];

  $webform_submission = [];

  $webform_submission['dd-bankaccount-email-text'] = [
    'name' => t('WaterAid bank account email text'),
    'description' => t('Custom text that should be added to emails.'),
  ];

  $tokens['webform_submission'] = $webform_submission;

  return [[], 'tokens' => $tokens];
}

/**
 * Implements hook_tokens().
 */
function webform_bankaccount_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {

  $replacements = [];

  if ($type === 'webform_submission' && !empty($data['webform_submission'])) {

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $data['webform_submission'];

    $data = $webform_submission->getData();
    $payment = $data['payment'] ?? NULL;
    foreach ($tokens as $name => $original) {
      if ($name == 'dd-bankaccount-email-text') {
        if ($payment !== NULL && $payment['payment_methods'] === 'bank_account') {
          $replacements[$original] = _wateraid_donations_get_email_text($webform_submission);
        }
        else {
          $replacements[$original] = "";
        }
      }
    }
  }

  return $replacements;
}

/**
 * Generate the dd-bankaccount-email-text token.
 *
 *  This was all originally put into DonationsWebformHandler, and just appended
 *  diretly onto the message.
 *  Moving to a token is the first part of a move to make all bits of text like
 *  this token based (so they can be moved around more easily by the editor).
 *
 * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
 *   The webform submission.
 *
 * @return \Drupal\Component\Render\MarkupInterface
 *   The email text.
 */
function _wateraid_donations_get_email_text(WebformSubmissionInterface $webform_submission): MarkupInterface {
  $text = '';
  $webform = $webform_submission->getWebform();
  $data = $webform_submission->getData();
  $dd_payment_date = $data[DonationConstants::DONATION_PREFIX . 'first_payment_date'];

  if ($dd_payment_date) {
    // String date: YYYY-MM-DD.
    $date_parts = explode('-', $dd_payment_date);
    $date_obj = DrupalDateTime::createFromArray(
      [
        'year' => $date_parts[0],
        'month' => $date_parts[1],
        'day' => $date_parts[2],
      ]
    );
    // Payment dates.
    $dd_date_formatted = $date_obj->format('d/m/Y');
    $dd_day_formatted = $date_obj->format('dS');
    $text .= '<p><strong>' . t('Direct Debit Payments') . '</strong><br/>';
    $text .= t('First payment will come out on @payment_date. Payment will continue on the @payment_day of every month.',
      [
        '@payment_date' => $dd_date_formatted,
        '@payment_day' => $dd_day_formatted,
      ]);
    $text .= '</p>';
  }
  // Payment account details.
  $dd_account_name = $data[DonationConstants::DONATION_PREFIX . 'account_name'];
  $dd_sort_code = $data[DonationConstants::DONATION_PREFIX . 'sort_code'];
  $dd_account_number = $data[DonationConstants::DONATION_PREFIX . 'account_number'];

  if (!empty($dd_sort_code) && !empty($dd_account_number) && !empty($dd_account_name)) {
    $text .= '<p><strong>' . t('Direct Debit Account') . '</strong><br/>';
    $text .= t('Account name:') . ' ' . $dd_account_name . '<br />';
    $text .= t('Account number:') . ' ' . substr_replace($dd_account_number, str_repeat('*', 4), 0, 4) . '<br />';
    $text .= t('Sort code:') . ' ' . substr_replace($dd_sort_code, str_repeat('*', 2), 0, 2) . '</p>';
  }

  // Direct Debit reference.
  $dd_reference = $data[DonationConstants::DONATION_PREFIX . 'instruction_reference'];

  if ($dd_reference) {
    $text .= '<p><strong>' . t('Direct Debit Reference') . '</strong><br/>';
    $text .= $dd_reference;
    $text .= '</p>';
  }

  // Load direct debit logo from block.
  // @todo Load block id from config somewhere.
  if ($block = BlockContent::load(71)) {
    $block_content = \Drupal::entityTypeManager()
      ->getViewBuilder('block_content')
      ->view($block);
    $direct_debit_logo = \Drupal::service('renderer')->renderRoot($block_content);
    $text .= '<table border="0" cellpadding="5" width="100%"><tr><td width="100%" align="right">' . $direct_debit_logo . '</tr></table>';
  }

  // Direct Debit declaration.
  $message_format = $webform->getThirdPartySetting('wateraid_donation_forms', 'direct_debit_guarantee')['format'];
  $dd_message = $webform->getThirdPartySetting('wateraid_donation_forms', 'direct_debit_guarantee')['value'];
  $text .= check_markup(\Drupal::token()
    ->replace($dd_message, ['webform_submission' => $webform_submission]), $message_format);

  // Have to return this as instance of MarkupInterface, or the Token module
  // will escape the HTML.
  // @See https://www.drupal.org/node/2580723
  return Markup::create($text);
}
