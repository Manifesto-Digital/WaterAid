<?php

declare(strict_types=1);

namespace Drupal\azure_blob_storage\Plugin\QueueWorker;

use Drupal\azure_blob_storage\service\AzureApi;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'azure_blob_storage_queue' queue worker.
 *
 * @QueueWorker(
 *   id = "azure_blob_storage_queue",
 *   title = @Translation("Blob Storage Queue"),
 *   cron = {"time" = 60},
 * )
 */
final class BlobStorageQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  private array $optionList = [];

  /**
   * Constructs a new BlobStorageQueue instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AzureApi $azureBlobStorageApi,
    private readonly LoggerChannelInterface $loggerChannel,
    private readonly QueueInterface $queue,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (Settings::get('azure_blob_storage_accountname')) {
      $this->azureBlobStorageApi->setAccountName(Settings::get('azure_blob_storage_accountname'));
    }

    if (Settings::get('azure_blob_storage_container')) {
      $this->azureBlobStorageApi->setContainer(Settings::get('azure_blob_storage_container'));
    }

    $this->loadWebformOptions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('azure_blob_storage.api'),
      $container->get('logger.channel.azure_blob_storage'),
      $container->get('queue')->get('azure_blob_storage_queue'),
    );
  }

  /**
   * Load the ids of all the available webforms.
   *
   */
  public function loadWebformOptions(): void {
    $config_factory = \Drupal::configFactory();

    $database = \Drupal::database();
    $result = $database->select('config', 'conf')
      ->fields('conf', ['name'])
      ->condition('name', 'webform.webform_options.communication%', 'LIKE')
      ->execute();

    $ids = [];

    foreach ($result as $record) {
      $ids[] = $record->name;
    }

    $option_config = $config_factory->loadMultiple($ids);

    foreach ($option_config as $key => $config_item) {
      $option_key  = str_replace('webform.webform_options.', '', $key);
      $raw_options = $config_item->getRawData()['options'];

      if(!empty($raw_options)) {
        foreach (explode(PHP_EOL, $raw_options) as $value) {
          $option_value = str_replace("'", '', explode(':', $value)[0]);

          if (!empty($option_value)) {
            $this->optionList[$option_key][] = $option_value;
          }
        }
      }
    }
  }

  /**
   * Map the given item to the desired standard structure.
   *
   * @param WebformSubmissionInterface $submission
   *   The submission to process
   *
   * @return array
   *   The mapped data
   */
  public function mapStandardItem(WebformSubmissionInterface $submission): array {
    $data = $submission->getData();
    $webform = $submission->getWebform();
    $fields = $webform->getElementsDecodedAndFlattened();

    foreach ($fields as $fieldKey => $fieldDefinition) {
      if (isset($fieldDefinition['#options']) && is_string($fieldDefinition['#options'])) {
        if (!empty($this->optionList[$fieldDefinition['#options']])) {
          $options = $this->optionList[$fieldDefinition['#options']];
          $values  = $data[$fieldKey];

          $data[$fieldKey] = [];

          foreach ($options as $option) {
            $data[$fieldKey][$option] = (in_array($option, $values)) ? TRUE : NULL;

          }
        }
      }
    }

    return $data;
  }

  /**
   * Map the given item to the desired donation structure.
   *
   * @param WebformSubmissionInterface $submission
   *   The submission to process
   *
   * @return array
   *   The mapped data
   */
  public static function mapDonationItem(WebformSubmissionInterface $submission): array {
    $submissionData = $submission->getData();

    $mappedData = [
      'contact_name_first' => $submissionData['contact_name']['first'],
      'contact_name_last' => $submissionData['contact_name']['last'],
      'contact_name_title' => $submissionData['contact_name']['title'],
      'contact_email' => $submissionData['contact_email']['email'],
      'contact_address' => [
        'address' => $submissionData['contact_address']['address'],
        'address_2' => $submissionData['contact_address']['address_2'],
        'city' => $submissionData['contact_address']['city'],
        'country' => $submissionData['contact_address']['country'],
        'paf_validated' => $submissionData['contact_address']['paf'],
        'postal_code' => $submissionData['contact_address']['postal_code'],
        'state_province' => $submissionData['contact_address']['state_province'],
      ],
      'communication_preferences' => [
        'opt_in_email' => NULL,
        'opt_in_phone' => NULL,
        'opt_in_sms' => NULL,
        'opt_in_social_media' => NULL,
        'opt_out_post' => TRUE
      ],
      'reason_for_donating' => $submissionData['prompt_reason'],
      'in_memory_firstname' => '',
      'in_memory_lastname' => '',
      'in_memory_relationship' => '',
      'in_memory_title' => '',
      'gift_aid' => (isset($submissionData['gift_aid']) && !empty($submissionData['gift_aid']['opt_in'])) ? TRUE  : NULL,
      'donation_currency' => $submissionData['donation__currency'],
      'donation_amount' => $submissionData['donation__amount'],
      'donation_date' => $submissionData['donation__date'],
      'donation_fulfillment_letter' => $submissionData['donation__fulfillment_letter'],
      'donation_status' => $submissionData['donation__status'],
      'donation_transaction_id' => '',
      'donation_payment_method' => $submissionData['donation__payment_method'],
      'dd_currency' => '',
      'dd_amount' => '',
      'dd_date' => '',
      'dd_fulfillment_letter' => '',
      'dd_status' => '',
      'dd_first_payment_date' => '',
      'dd_frequency' => '',
      'dd_sort_code' => '',
      'dd_account_number' => '',
      'dd_account_name' => '',
      'dd_instruction_reference' => '',
      'utm_campaign' => '',
      'utm_source' => '',
      'utm_content' => '',
      'utm_medium' => '',
      'fund_code' => '',
      'package_id' => $submissionData['donation__package_code'],
      'campaign' => '',
      'segment_code' => ''
    ];

    if (isset($submissionData['in_memory'])) {
      $mappedData['in_memory_firstname'] = $submissionData['in_memory']['in_memory_firstname'];
      $mappedData['in_memory_lastname'] = $submissionData['in_memory']['in_memory_lastname'];
      $mappedData['in_memory_relationship'] = $submissionData['in_memory']['in_memory_relationship'];
      $mappedData['in_memory_title'] = $submissionData['in_memory']['in_memory_title'];
    }

    if (!empty($submissionData['communication_preferences']['opt_in_email'])) {
      $mappedData['communication_preferences']['opt_in_email'] = TRUE;
    }

    if (!empty($submissionData['communication_preferences']['opt_in_post'])) {
      $mappedData['communication_preferences']['opt_out_post'] = TRUE;
    }

    if ($submissionData['donation__payment_method'] === 'bank_account') {
      $mappedData['dd_currency'] = $submissionData['payment']['currency'];
      $mappedData['dd_amount'] = $submissionData['payment']['amount'];
      $mappedData['dd_date'] = $submissionData['payment']['date'];
      $mappedData['dd_fulfillment_letter'] = $submissionData['payment']['fulfillment_letter'];
      $mappedData['dd_status'] = $submissionData['payment']['dd_status'];
      $mappedData['dd_first_payment_date'] = $submissionData['payment']['first_payment_date'];
      $mappedData['dd_frequency'] = $submissionData['payment']['frequency'];
      $mappedData['dd_sort_code'] = $submissionData['payment']['sort_code'];
      $mappedData['dd_account_number'] = $submissionData['payment']['account_number'];
      $mappedData['dd_account_name'] = $submissionData['payment']['account_name'];
      $mappedData['dd_instruction_reference'] = $submissionData['payment']['instruction_reference'];
    }

    if (!empty($submissionData['donation__transaction_id'])) {
      $mappedData['donation_transaction_id'] = $submissionData['donation__transaction_id'];
    }

    return $mappedData;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (!isset($data['webform_id']) || !isset($data['sid'])) {
      return;
    }

    // If a number of tries has been provided, use it. If not, assume this is
    // the first try because our code will definitely add the number.
    $tries = $data['tries'] ?? 0;

    // For testing purposes, we'll let 5 attempts trigger an error.
    $test_fail = \Drupal::state()->get('azure_blog_storage_test_fails', 5);

    // If we've had five tries, we'll give up.
    if ($tries >= 5 || $test_fail < 5) {
      $this->loggerChannel->critical($this->t('Error sending submission :sid from the :webform webform to the Azure storage blob', [
        ':sid' => $data['sid'],
        ':webform' => $data['webform_id'],
      ]));

      // If tries are more than five, do not continue. Otherwise, let our fake
      // fails also push to storage so we do not lose data.
      if ($tries >= 5) {
        return;
      }
      else {
        $test_fail++;
        \Drupal::state()->set('azure_blog_storage_test_fails', $test_fail);
      }
    }

    $error = FALSE;

    /** @var \Drupal\webform\WebformSubmissionInterface $submission */
    if ($submissions = $this->entityTypeManager->getStorage('webform_submission')
      ->loadByProperties([
        'sid' => $data['sid'],
        'webform_id' => $data['webform_id'],
      ])) {
      if (count($submissions) == 1) {
        $submission = reset($submissions);
        $name = $data['webform_id'] . '-' . $submission->uuid() . '.json';
        $is_donation = FALSE;

        try {
          $submission->getWebform()->getHandler('wateraid_donations');
          $is_donation = TRUE;
        }
        catch (\Exception $e) {
          $error = TRUE;
        }

        if (!$error && $this->azureBlobStorageApi->blobPut($this->getPrefixedName($name, $is_donation), $this->generateBlobArray($submission, $is_donation), TRUE)) {
          // The submission has been successfully stored in the blob, so we can
          // delete it from the website.
//          $submission->delete();
        }
        else {
          $error = TRUE;
        }
      }
      else {
        $error = TRUE;
      }
    }
    else {
      $error = TRUE;
    }

    if ($error) {

      // If something went wrong, we'll push the data back into the queue to
      // try again.
      $tries++;
      $data['tries'] = $tries;

      $this->queue->createItem($data);
    }
  }

  /**
   * Get the prefixed name.
   *
   * Used to ensure that all donation webforms start with the 'donation_'
   *
   * @param string $name
   *   The webform name to apply the prefix to.
   * @param bool $isDonationSubmission
   *   A boolean indicating if the form type is a donation form.
   *
   * @return string
   *   The prefixed webform name.
   */
  private function getPrefixedName(string $name, bool $isDonationSubmission = false): string {
    if ($isDonationSubmission) {
      if (str_starts_with($name, 'donation_')) {
        return $name;
      }
      else {
        return "donation_{$name}";
      }
    }

    return $name;
  }

  /**
   * Generates the data structure to be stored in Azure.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The webform submission.
   * @param bool $isDonationSubmission
   *    A boolean indicating if the submission is a donation submission/
   *
   * @return array
   *   A structured array of data.
   */
  private function generateBlobArray(WebformSubmissionInterface $submission, bool $isDonationSubmission = false): array {
    $webform     = $submission->getWebform();
    $owner       = $webform->getOwner();

    $date = ($submitted = $submission->getCompletedTime()) ? DrupalDateTime::createFromTimestamp($submitted) : new DrupalDateTime();

    $this->loggerChannel->notice(print_r($submission->getData(), TRUE));

    return [
      'id' => $submission->uuid(),
      'webform' => $this->getPrefixedName($webform->id(), $isDonationSubmission),
      'webform_owner' => ($owner) ? $owner->label() : 'Anonymous',
      'webform_last_updated' => '',
      'submission_remote_address' => $submission->getRemoteAddr(),
      'submission_data' => $isDonationSubmission ? self::mapDonationItem($submission) : $this->mapStandardItem($submission),
      'submission_date' => $date->format(\DateTimeInterface::ATOM),
    ];
  }

}
