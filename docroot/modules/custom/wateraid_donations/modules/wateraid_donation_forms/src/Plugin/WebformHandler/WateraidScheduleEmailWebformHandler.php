<?php

namespace Drupal\wateraid_donation_forms\Plugin\WebformHandler;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\wateraid_donation_forms\FallbackWebformHandlerTrait;
use Drupal\wateraid_donation_forms\PaymentFrequencyWebformHandlerTrait;
use Drupal\webform\Twig\WebformTwigExtension;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_scheduled_email\Plugin\WebformHandler\ScheduleEmailWebformHandler;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "wateraid_schedule_email",
 *   label = @Translation("WaterAid Donation Scheduled Email"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a webform submission via an email on cron."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL
 * )
 */
class WateraidScheduleEmailWebformHandler extends ScheduleEmailWebformHandler {

  use PaymentFrequencyWebformHandlerTrait, FallbackWebformHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + $this->getPaymentFrequencyDefaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary(): array {
    $configuration = $this->getConfiguration();
    $settings = $configuration['settings'];
    if ($this->configuration['payment_frequency']) {
      $items[] = $this->getPaymentFrequencySummary($this->configuration['payment_frequency']);
    }
    if (!empty($settings['fallback_plugin'])) {
      $items[] = $this->t('<strong>Fallback method:</strong> [@plugin_id]', [
        '@plugin_id' => $settings['fallback_plugin'],
      ]);
    }
    if (!empty($items)) {
      return [
        '#theme' => 'item_list',
        '#items' => $items,
      ];
    }
    return parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form = $this->buildFallbackForm($form, $form_state, 'Body');
    return $this->buildPaymentFrequencyForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(WebformSubmissionInterface $webform_submission, array $message) {
    // Quick check if the message array is not empty.
    // As we may not want to send out an email, and the parent class cant handle
    // this.
    if (!empty($message)) {
      return parent::sendMessage($webform_submission, $message);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\wateraid_donation_forms\FallbackWebformHandlerTrait
   */
  public function getMessage(WebformSubmissionInterface $webform_submission): array {
    // Return an empty message if the payment frequency doesn't match. If the
    // payment frequency isn't given, then allow for any frequency.
    if ($this->paymentFrequencyMatches($webform_submission) === FALSE) {
      return [];
    }
    // If we get here, then we are allowed to send the email for frequency.
    $message = parent::getMessage($webform_submission);

    // Alter body based on the mail system sender.
    if ($this->configuration['html'] && $this->supportsHtml()) {

      $site_url = Url::fromRoute('<front>', [], ['absolute' => TRUE]);
      // Dirty trick to fix image urls to absolute.
      // @todo This in a more proper way (e.g.
      // http://cgit.drupalcode.org/relative_to_absolute_url/tree/src/reltoabsAlterResponse.php)
      // @todo For links as well?
      $message['body'] = str_replace('src="/', 'src="' . $site_url->toString(), $message['body']);
      if ($this->getMailSystemFormatter() == 'swiftmailer') {
        // SwiftMailer requires that the body be valid Markup.
        $message['body'] = Markup::create($this->getBodyorFallback($webform_submission, $message['body']));
      }
    }
    else {
      // Since Drupal might be rendering a token into the body as markup
      // we need to decode all HTML entities which are being sent as plain text.
      $message['body'] = html_entity_decode($this->getBodyorFallback($webform_submission, $message['body']));
    }

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMessageAttachments(WebformSubmissionInterface $webform_submission): array {
    $attachments = parent::getMessageAttachments($webform_submission);
    if ($webform_submission->hasField('file_id')) {
      $file_id = $webform_submission->get('file_id')->getValue();
      if (!empty($file_id)) {
        $file = File::load($file_id[0]['value']);
        if ($file) {
          $filepath = \Drupal::service('file_system')->realpath($file->getFileUri());
          $attachments[] = [
            'filecontent' => file_get_contents($filepath),
            'filename' => $file->getFilename(),
            'filemime' => $file->getMimeType(),
            // Add URL to be used by resend webform.
            'file' => $file,
          ];
        }
      }
    }

    return $attachments;
  }

  /**
   * Get Body or its fallback.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform Submission interface.
   * @param string $body
   *   Body value.
   *
   * @return mixed|null
   *   The body or its fallback.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function getBodyorFallback(WebformSubmissionInterface $webform_submission, string $body): array|string {
    // Check on Fallback plugin.
    if (!empty($this->configuration['fallback_plugin'])) {
      /** @var \Drupal\wateraid_donation_forms\FallbackInterface $fallback_plugin */
      $fallback_plugin = $this->getFallbackManager()->createInstance($this->configuration['fallback_plugin']);
      // Process "fallback_message" on "body".
      if ($fallback_plugin->isApplicable($webform_submission)) {
        // Apply fallback instead of body.
        $configuration_value = $this->configuration['fallback_message'] ?? NULL;

        // Render fallback in the same way EmailWebformHandler::getMessage
        // treats $this->configuration['body'].
        $token_options = [
          'email' => TRUE,
          'excluded_elements' => $this->configuration['excluded_elements'],
          'ignore_access' => $this->configuration['ignore_access'],
          'exclude_empty' => $this->configuration['exclude_empty'],
          'exclude_empty_checkbox' => $this->configuration['exclude_empty_checkbox'],
          'exclude_attachments' => $this->configuration['exclude_attachments'],
          'html' => ($this->configuration['html'] && $this->supportsHtml()),
        ];

        // If Twig enabled render and body, render the Twig template.
        if ($this->configuration['twig']) {
          $return = WebformTwigExtension::renderTwigTemplate($webform_submission, $configuration_value, $token_options);
        }
        else {
          // Get replace token values.
          $token_value = $this->replaceTokens($configuration_value, $webform_submission, [], $token_options);
          // Decode entities for message values except the HTML message body.
          if (!empty($token_value) && is_string($token_value) && !($token_options['html'])) {
            $token_value = Html::decodeEntities($token_value);
          }

          $return = $token_value;
        }

        return $return;
      }
    }
    // If not applicable, then just return the body.
    return $body;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    // Fallback settings.
    $this->configuration['fallback_plugin'] = $form_state->getValue([
      'fallback_settings',
      'fallback_plugin',
    ]) ?: NULL;
    $this->configuration['fallback_message'] = $form_state->getValue([
      'fallback_settings',
      'fallback_wrapper',
      'fallback_message',
    ]) ?: NULL;
    parent::submitConfigurationForm($form, $form_state);
  }

}
