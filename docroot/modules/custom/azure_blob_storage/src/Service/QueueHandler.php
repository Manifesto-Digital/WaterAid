<?php

namespace Drupal\azure_blob_storage\Service;


use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Site\Settings;
use Drupal\group\Entity\GroupInterface;
use Drupal\wa_crm_logs\Service\Logging;
use Drupal\webform\WebformSubmissionInterface;

class QueueHandler {

  /**
   * The main CRM Transfer queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  private QueueInterface $mainQueue;

  /**
   * The dead letter queue for failed items.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  private QueueInterface $deadLetterQueue;

  /**
   * The option list array.
   *
   * @var array
   */
  private array $optionList = [];

  /**
   * @param ConfigFactory $configFactory
   *   The config factory.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   An entity type manager.
   * @param Connection $database
   *   a database connection.
   * @param QueueFactory $queueService
   *   The queue factory.
   * @param AzureApi $azureBlobStorageApi
   *   The Azure API service.
   * @param Logging $logging
   *   The CRM logging service.
   */
  public function __construct(
    private readonly ConfigFactory $configFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Connection $database,
    QueueFactory $queueService,
    private readonly AzureApi $azureBlobStorageApi,
    private readonly Logging $logging,
  ) {
    $this->mainQueue = $queueService->get('azure_blob_storage_queue');
    $this->deadLetterQueue = $queueService->get('azure_blob_storage_dead_letter_queue');

    if (Settings::get('azure_blob_storage_accountname')) {
      $this->azureBlobStorageApi->setAccountName(Settings::get('azure_blob_storage_accountname'));
    }

    if (Settings::get('azure_blob_storage_container')) {
      $this->azureBlobStorageApi->setContainer(Settings::get('azure_blob_storage_container'));
    }

    $this->loadWebformOptions();
  }

  /**
   * Generates the data structure to be stored in Azure.
   *
   * @param WebformSubmissionInterface $submission
   *   The webform submission.
   * @param bool $isDonationSubmission
   *    A boolean indicating if the submission is a donation submission/
   *
   * @return array
   *   A structured array of data.
   */
  private function generateBlobArray(WebformSubmissionInterface $submission, bool $isDonationSubmission = false): array {
    $webform = $submission->getWebform();
    $owner = $webform->getOwner();
    $date = ($submitted = $submission->getCompletedTime()) ? DrupalDateTime::createFromTimestamp($submitted) : new DrupalDateTime();

    if ($isDonationSubmission) {
      if ($webform->id() === 'pay_in_your_fundraising') {
        $submission_data = self::mapPayInFundraising($submission);
      }
      else {
        $submission_data = self::mapDonationItem($submission);
      }

    }
    else {
      $submission_data = $this->mapStandardItem($submission);
    }

    return [
      'id' => $submission->uuid(),
      'webform' => $this->getPrefixedName($webform->id(), $isDonationSubmission),
      'webform_owner' => ($owner) ? $owner->label() : 'Anonymous',
      'webform_last_updated' => '',
      'submission_remote_address' => $submission->getRemoteAddr(),
      'submission_data' => $submission_data,
      'submission_date' => $date->format(\DateTimeInterface::ATOM),
    ];
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
      if (str_starts_with($name, 'donation_') || str_starts_with($name, 'pay_in_your_fundraising')) {
        return $name;
      }
      else {
        return "donation_{$name}";
      }
    }

    return $name;
  }

  /**
   * Load the available webform options.
   */
  private function loadWebformOptions(): void {
    $result = $this->database->select('config', 'conf')
      ->fields('conf', ['name'])
      ->condition('name', 'webform.webform_options.communication%', 'LIKE')
      ->execute();

    $ids = [];

    foreach ($result as $record) {
      $ids[] = $record->name;
    }

    $option_config = $this->configFactory->loadMultiple($ids);

    foreach ($option_config as $key => $config_item) {
      $option_key = str_replace('webform.webform_options.', '', $key);
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
      'contact_name_first' => self::mapSafeValue($submissionData['contact_name'], 'first'),
      'contact_name_last' => self::mapSafeValue($submissionData['contact_name'], 'last'),
      'contact_name_title' => self::mapSafeValue($submissionData['contact_name'], 'title'),
      'contact_email' => self::mapSafeValue($submissionData['contact_email'], 'email'),
      'contact_address' => [
        'address' => self::mapSafeValue($submissionData['contact_address'],'address'),
        'address_2' => self::mapSafeValue($submissionData['contact_address'],'address_2'),
        'city' => self::mapSafeValue($submissionData['contact_address'],'city'),
        'country' => self::mapSafeValue($submissionData['contact_address'],'country'),
        'paf_validated' => self::mapSafeValue($submissionData['contact_address'],'paf'),
        'postal_code' => self::mapSafeValue($submissionData['contact_address'],'postal_code'),
        'state_province' => self::mapSafeValue($submissionData['contact_address'],'state_province'),
      ],
      'contact_phone' => self::mapSafeValue($submissionData, 'contact_phone'),
      'communication_preferences' => [
        'opt_in_email' => NULL,
        'opt_in_phone' => NULL,
        'opt_in_sms' => NULL,
        'opt_in_social_media' => NULL,
        'opt_out_post' => FALSE,
      ],
      'reason_for_donating' => self::mapSafeValue($submissionData,'prompt_reason'),
      'in_memory_firstname' => '',
      'in_memory_lastname' => '',
      'in_memory_relationship' => '',
      'in_memory_title' => '',
      'gift_aid' => (isset($submissionData['gift_aid']) && !empty($submissionData['gift_aid']['opt_in'])) ? TRUE : NULL,
      'donation_currency' => self::mapSafeValue($submissionData,'donation__currency'),
      'donation_amount' => self::mapSafeValue($submissionData,'donation__amount'),
      'donation_date' => self::mapSafeValue($submissionData,'donation__date'),
      'donation_fulfillment_letter' => self::mapSafeValue($submissionData,'donation__fulfillment_letter'),
      'donation_status' => self::mapSafeValue($submissionData, 'donation__status'),
      'donation_transaction_id' => '',
      'donation_payment_method' => self::mapSafeValue($submissionData, 'donation__payment_method'),
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
      'package_id' => self::mapSafeValue($submissionData, 'donation__package_code'),
      'campaign' => '',
      'segment_code' => ''
    ];

    if (isset($submissionData['in_memory'])) {
      $mappedData['in_memory_firstname'] = self::mapSafeValue($submissionData['in_memory'],'in_memory_firstname');
      $mappedData['in_memory_lastname'] = self::mapSafeValue($submissionData['in_memory'],'in_memory_lastname');
      $mappedData['in_memory_relationship'] = self::mapSafeValue($submissionData['in_memory'],'in_memory_relationship');
      $mappedData['in_memory_title'] = self::mapSafeValue($submissionData['in_memory'],'in_memory_title');
    }

    if (!empty($submissionData['communication_preferences']['opt_in_email']) || in_array('opt_in_email', $submissionData['communication_preferences'])) {
      $mappedData['communication_preferences']['opt_in_email'] = TRUE;
    }

    if (!empty($submissionData['communication_preferences']['opt_out_post']) || in_array('opt_out_post', $submissionData['communication_preferences'])) {
      $mappedData['communication_preferences']['opt_out_post'] = TRUE;
    }

    if (
      isset($submissionData['donation__payment_method']) &&
      isset($submissionData['payment']) &&
      ($submissionData['donation__payment_method'] === 'bank_account')
    ) {
      $mappedData['dd_currency'] = self::mapSafeValue($submissionData['payment'], 'currency');
      $mappedData['dd_amount'] = self::mapSafeValue($submissionData['payment'], 'amount');
      $mappedData['dd_date'] = self::mapSafeValue($submissionData['payment'], 'date');
      $mappedData['dd_fulfillment_letter'] = self::mapSafeValue($submissionData['payment'], 'fulfillment_letter');
      $mappedData['dd_status'] = self::mapSafeValue($submissionData['payment'], 'dd_status');
      $mappedData['dd_first_payment_date'] = self::mapSafeValue($submissionData['payment'], 'first_payment_date');
      $mappedData['dd_frequency'] = self::mapSafeValue($submissionData['payment'], 'frequency');
      $mappedData['dd_sort_code'] = self::mapSafeValue($submissionData['payment'], 'sort_code');
      $mappedData['dd_account_number'] = self::mapSafeValue($submissionData['payment'], 'account_number');
      $mappedData['dd_account_name'] = self::mapSafeValue($submissionData['payment'],'account_name');
      $mappedData['dd_instruction_reference'] = self::mapSafeValue($submissionData['payment'], 'instruction_reference');
    }

    if (!empty($submissionData['donation__transaction_id'])) {
      $mappedData['donation_transaction_id'] = self::mapSafeValue($submissionData, 'donation__transaction_id');
    }

    return $mappedData;
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
  public static function mapPayInFundraising(WebformSubmissionInterface $submission): array {
    $mappedData = self::mapDonationItem($submission);
    $submissionData = $submission->getData();

    $keys = [
      'have_you_had_a_talk_or_workshop_from_a_wateraid_speaker_' => 'have_you_had_a_talk_or_workshop_from_a_wateraid_speaker',
      'how_was_the_money_raised_' => 'how_was_the_money_raised',
      'organisation_name' => 'organisation_name',
      'organisation_name_company' => 'organisation_name_company',
      'organisation_type' => 'organisation_type',
      'team_name' => 'team_name',
      'type_of_service_organisation_or_club' => 'type_of_service_organisation_or_club',
      'university_name' => 'university_name',
      'what_event_did_you_take_part_in_company' => 'what_event_did_you_take_part_in_company',
      'what_event_did_you_take_part_in_individual' => 'what_event_did_you_take_part_in_individual',
      'what_kind_of_event_challenge_company_' => 'what_kind_of_event_challenge_company',
      'what_kind_of_event_challenge_faith_group_' => 'what_kind_of_event_challenge_faith_group',
      'what_kind_of_event_challenge_individual_' => 'what_kind_of_event_challenge_individual',
      'what_kind_of_event_challenge_school_' => 'what_kind_of_event_challenge_school',
      'what_was_the_name_of_the_event_' => 'what_was_the_name_of_the_event',
      'what_was_the_name_of_the_event_water_company_event' => 'what_was_the_name_of_the_event_water_company_event',
      'when_did_the_event_take_place_company' => 'when_did_the_event_take_place_company',
      'when_did_the_event_take_place_individual' => 'when_did_the_event_take_place_individual',
    ];

    foreach ($keys as $sourceKey => $destinationKey) {
      $mappedData[$destinationKey] = self::mapSafeValue($submissionData, $sourceKey);
    }

    return $mappedData;
  }

  /**
   * Map as safe value for the given data array and key.
   *
   * @param array $submissionData
   *   The submission data array.
   * @param string $key
   *   The key of the value to process
   * @return mixed|null
   */
  public static function mapSafeValue(array $submissionData, string $key): mixed{
    if (isset($submissionData[$key])) {
      return $submissionData[$key];
    }

    return NULL;
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
          $values = $data[$fieldKey];

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
   * @param array $data
   *   The data to process.
   *
   * @return void
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processQueueItem(array $data): void {
    if (!isset($data['webform_id']) || !isset($data['sid'])) {
      return;
    }

    $submissions = $this->entityTypeManager->getStorage('webform_submission')
      ->loadByProperties([
        'sid' => $data['sid'],
        'webform_id' => $data['webform_id'],
      ]);

    if ($submissions && count($submissions)) {
      $submission = reset($submissions);
      $name = $data['webform_id'] . '-' . $submission->uuid() . '.json';
      $is_donation = FALSE;

      try {
        $submission->getWebform()->getHandler('wateraid_donations');
        $is_donation = TRUE;
      }
      catch (\Exception $e) {
      }

      // Get the group for logging purposes.
      $group = $this->getGroup($submission);

      try {
        if ($this->azureBlobStorageApi->blobPut($this->getPrefixedName($name, $is_donation), $this->generateBlobArray($submission, $is_donation), TRUE)) {
          // The submission has been successfully stored in the blob, so we can
          // delete it from the website.
          $submission->delete();
        }
        else {
          if ($log = $this->logging->createLog("Unable to store webform blob {$data['sid']} from the {$data['webform_id']} webform to the Azure storage blob", $submission, $group)) {
            $log->save();
          }
        }
      }
      catch (\Exception $e) {
        if ($log = $this->logging->createLog($e, $submission, $group)) {
          $log->save();
        }
      }
    }
    else {
      if ($log = $this->logging->createLog("Unable to load webform {$data['sid']} from the {$data['webform_id']} webform to the Azure storage blob", $data['sid'])) {
        $log->save();
      }
    }
  }

  /**
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroup(WebformSubmissionInterface $submission): ?GroupInterface {
    $group = NULL;

    /** @var \Drupal\group\Entity\Storage\GroupRelationshipStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_relationship');

    if ($relationships = $storage->loadByEntity($submission->getWebform())) {
      $group = $relationships[0]->getGroup();
    }

    return $group;
  }

  /**
   * A custom method provided by the service.
   */
  public function processQueue(): void {
    $items_to_enqueue = [];

    while($item = $this->mainQueue->claimItem(0)) {
      $data = $item->data;

      try {
        $this->processQueueItem($data);
        $this->mainQueue->deleteItem($item);
      }
      catch (\Exception $e) {
        $data['tries']++;
        $this->mainQueue->deleteItem($item);

        if ($data['tries'] < 5) {
          $items_to_enqueue[] = $data;;
        }
        else {
          $this->deadLetterQueue->createItem($data);
        }
      }
    }

    foreach ($items_to_enqueue as $item) {
      $this->mainQueue->createItem($item);
    }
  }

}
