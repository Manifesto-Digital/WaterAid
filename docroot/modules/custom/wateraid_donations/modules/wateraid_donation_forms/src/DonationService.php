<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\webform\WebformSubmissionInterface;
use Psr\Log\LoggerInterface;

/**
 * Donation Service.
 *
 * @package Drupal\wateraid_donation_forms
 */
class DonationService implements DonationServiceInterface {

  /**
   * Payment frequency plugin manager.
   */
  protected PaymentFrequencyPluginManager $paymentFrequencyPluginManager;

  /**
   * Payment provider plugin manager.
   */
  protected PaymentProviderPluginManager $paymentProviderPluginManager;

  /**
   * Payment type plugin manager.
   */
  protected PaymentTypePluginManager $paymentTypePluginManager;

  /**
   * A logger instance.
   */
  protected LoggerInterface $logger;

  /**
   * Constructs the service.
   *
   * @param \Drupal\wateraid_donation_forms\PaymentFrequencyPluginManager $payment_frequency_plugin_manager
   *   Payment Frequency Plugin Manager.
   * @param \Drupal\wateraid_donation_forms\PaymentProviderPluginManager $payment_provider_plugin_manager
   *   Payment Provider Plugin Manager.
   * @param \Drupal\wateraid_donation_forms\PaymentTypePluginManager $payment_type_plugin_manager
   *   Paymet Type Plugin Manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(PaymentFrequencyPluginManager $payment_frequency_plugin_manager, PaymentProviderPluginManager $payment_provider_plugin_manager, PaymentTypePluginManager $payment_type_plugin_manager, LoggerInterface $logger) {
    $this->paymentFrequencyPluginManager = $payment_frequency_plugin_manager;
    $this->paymentProviderPluginManager = $payment_provider_plugin_manager;
    $this->paymentTypePluginManager = $payment_type_plugin_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentProviderLabel($plugin_id): mixed {
    try {
      $instance = $this->paymentProviderPluginManager->createInstance($plugin_id);
    }
    catch (PluginException $e) {
      $this->logger->error('Payment Provider id :plugin_id does not exist', [
        ':plugin_id' => $plugin_id,
      ]);
      return FALSE;
    }
    return $instance->getPluginDefinition()['ui_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentFrequencies($id = NULL): array {
    $payment_frequencies = [];
    $payment_frequency_definitions = $this->paymentFrequencyPluginManager->getDefinitions();
    foreach ($payment_frequency_definitions as $plugin_id => $payment_frequency_definition) {
      if ($id === NULL || $payment_frequency_definition['id'] === $id) {
        try {
          $payment_frequencies[$plugin_id] = $this->paymentFrequencyPluginManager->createInstance($plugin_id);
        }
        catch (PluginException $e) {
          $this->logger->error('Payment Frequency id :plugin_id does not exist', [
            ':plugin_id' => $plugin_id,
          ]);
        }
      }
    }
    return $payment_frequencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentTypes($id = NULL): array {
    $payment_types = [];
    $payment_type_definitions = $this->paymentTypePluginManager->getDefinitions();
    foreach ($payment_type_definitions as $plugin_id => $payment_type_definition) {
      if ($id === NULL || $payment_type_definition['id'] === $id) {
        try {
          $payment_types[$plugin_id] = $this->paymentTypePluginManager->createInstance($plugin_id);
        }
        catch (PluginException $e) {
          $this->logger->error('Payment Type id :plugin_id does not exist', [
            ':plugin_id' => $plugin_id,
          ]);
        }
      }
    }
    return $payment_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentProvidersByType($type = NULL): array {
    $payment_providers = [];
    $payment_provider_definitions = $this->paymentProviderPluginManager->getDefinitions();
    foreach ($payment_provider_definitions as $plugin_id => $payment_provider_definition) {
      // Also include Payment Providers that have type 'all'.
      if ($type === NULL || $payment_provider_definition['type'] === $type || $payment_provider_definition['type'] === 'all') {
        try {
          $payment_providers[$plugin_id] = $this->paymentProviderPluginManager->createInstance($plugin_id);
        }
        catch (PluginException $e) {
          $this->logger->error('Payment Provider id :plugin_id does not exist', [
            ':plugin_id' => $plugin_id,
          ]);
        }
      }
    }
    return $payment_providers;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentFrequency($plugin_id): object|bool {
    try {
      return $this->paymentFrequencyPluginManager->createInstance($plugin_id);
    }
    catch (PluginException $e) {
      $this->logger->error('Payment Frequency id :plugin_id does not exist', [
        ':plugin_id' => $plugin_id,
      ]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentProvider($plugin_id): object|bool {
    try {
      return $this->paymentProviderPluginManager->createInstance($plugin_id);
    }
    catch (PluginException $e) {
      $this->logger->error('Payment Provider id :plugin_id does not exist', [
        ':plugin_id' => $plugin_id,
      ]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentType($plugin_id): object|bool {
    try {
      return $this->paymentTypePluginManager->createInstance($plugin_id);
    }
    catch (PluginException $e) {
      $this->logger->error('Payment Type id :plugin_id does not exist', [
        ':plugin_id' => $plugin_id,
      ]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInMemoryData(WebformSubmissionInterface $webform_submission): bool|array {
    // Check eligibility of the parent Webform by ensuring it has a
    // "donations_webform_in_memory" WebformElement.
    $elements = $webform_submission->getWebform()->getElementsDecodedAndFlattened();
    $element_donations_webform_in_memory = NULL;
    foreach ($elements as $element_key => $element) {
      if ($element['#type'] === 'donations_webform_in_memory') {
        $element_donations_webform_in_memory = $element_key;
        break;
      }
    }

    // Do not proceed if no such element exists on the Webform.
    if ($element_donations_webform_in_memory === NULL) {
      return FALSE;
    }

    // Extract relevant data from In Memory element.
    $inMemoryData = $webform_submission->getElementData($element_donations_webform_in_memory);

    // If the element exists but is empty return empty array.
    if (is_null($inMemoryData)) {
      return [];
    }

    return $inMemoryData;
  }

  /**
   * {@inheritDoc}
   */
  public function getFixedPeriodDateEnd(WebformSubmissionInterface $webform_submission): \DateTime|NULL {
    if (!is_array($webform_submission->getData()['donation_amount']['duration'])) {
      if ($duration = $webform_submission->getData()['donation_amount']['duration'] ?? NULL) {
        $datetime = new \DateTime();
        $datetime->setTimestamp($webform_submission->getCreatedTime());
        return $datetime->add(new \DateInterval('P' . ($duration - 1) . 'M'));
      }
    }
    return NULL;
  }

  /**
   * Get a token supported string per frequency.
   *
   *  Do not wrap these in t() strings!
   *
   * @param string|null $payment_frequency
   *   The payment frequency machine name.
   *
   * @return string
   *   The human-readable text for the payment frequency.
   */
  public static function getDefaultPaymentFrequencyProgressMessage(?string $payment_frequency = NULL): string {
    switch ($payment_frequency) {
      case 'one_off':
        return 'You are making a one-off donation of [donation:currency-sign][donation:amount].';

      case 'recurring':
        return 'You are making a monthly donation of [donation:currency-sign][donation:amount].';

      case 'fixed_period':
        return 'You are making a monthly donation of [donation:currency-sign][donation:amount] for [donation:duration] months.';

      case NULL:
      default:
        return 'You are donating [donation:currency-sign][donation:amount].';
    }
  }

}
