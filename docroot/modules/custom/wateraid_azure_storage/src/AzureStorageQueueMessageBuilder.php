<?php

namespace Drupal\wateraid_azure_storage;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\wateraid_azure_storage\Element\WaterAidWebformColumns;
use Drupal\wateraid_azure_storage\Utility\MessageMetadataHelper;
use Drupal\wateraid_azure_storage\Utility\MessageSubmissionDataHelper;
use Drupal\wateraid_forms\Plugin\WebformExporter\WaterAidDelimitedExporter;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;

/**
 * Queue Message Builder.
 *
 * @package Drupal\wateraid_azure_storage
 */
class AzureStorageQueueMessageBuilder implements AzureStorageQueueMessageBuilderInterface {

  /**
   * The webform submission storage.
   */
  protected EntityStorageInterface $webformSubmissionStorage;

  /**
   * The webform token manager.
   */
  protected WebformTokenManagerInterface $tokenManager;

  /**
   * The webform element manager.
   */
  protected WebformElementManagerInterface $elementManager;

  /**
   * The date formatter service.
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Static cached URL param values.
   *
   * @var mixed[]|null
   */
  protected ?array $urlParams = NULL;

  /**
   * AzureStorageQueueMessageBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WebformTokenManagerInterface $token_manager, WebformElementManagerInterface $element_manager, DateFormatterInterface $date_formatter) {
    $this->webformSubmissionStorage = $entity_type_manager->getStorage('webform_submission');
    $this->tokenManager = $token_manager;
    $this->elementManager = $element_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function create(WebformSubmissionInterface $webform_submission, array $config = []): AzureStorageQueueMessage {

    // Init message transformation.
    $settings = $config['settings'] ?? NULL;
    $message_data = [];

    // The below method call requires an array of options.
    $export_options = WaterAidWebformColumns::getExportOptions();

    // 1. Get submission data.
    if (!empty($settings[MessageSubmissionDataHelper::FIELD])) {

      $field_definitions = $this->webformSubmissionStorage->getFieldDefinitions();
      $webform = $webform_submission->getWebform();
      $elements = $this->tokenManager->replace($webform->getElementsInitializedFlattenedAndHasValue(), $webform);
      $parent_collection = [];

      // Append field definitions with arbitrary Webform Submission Entity
      // fields that are also available in the CSV export.
      $field_definitions += WaterAidWebformColumns::getArbitraryFieldDefinitions();

      foreach ($settings[MessageSubmissionDataHelper::FIELD] as $item) {

        $original_key = $item['original_key'] ?? NULL;
        $new_key = $item['new_key'] ?? NULL;
        $parent_key = $item['parent_key'] ?? NULL;

        // First see if the value can be extracted from an entity field.
        if (array_key_exists($original_key, $field_definitions)) {
          $header = [];
          try {
            $record = $this->formatRecordFieldDefinitionValue($webform_submission, $field_definitions[$original_key], $new_key);
          }
          catch (EntityMalformedException $e) {
            $record = NULL;
          }
        }
        // If not, then presumably this will be a Webform element.
        // Ignore 'donation_amount' because that item is an "original key" in
        // the "donations_webform_payment" element in the next clause.
        elseif (array_key_exists($original_key, $elements) && $original_key !== 'donation_amount') {
          // Extract values ::buildExportRecord() and ::buildExportHeader().
          // NOTE: Because this is a Webform internal and actually used to
          // write columns to a row, we need to capture the output and merge it
          // to ensure we're not returning a nested array.
          $header = $this->elementManager->invokeMethod('buildExportHeader', $elements[$original_key], $export_options);
          $record = $this->elementManager->invokeMethod('buildExportRecord', $elements[$original_key], $webform_submission, $export_options);
        }
        // If not, then presumably this is a composite Webform element.
        elseif ($parent_key !== NULL && array_key_exists($parent_key, $elements)) {
          // Check if we didn't already load this parent element.
          if (array_key_exists($parent_key, $parent_collection) === FALSE) {
            $parent_header = $this->elementManager->invokeMethod('buildExportHeader', $elements[$parent_key], $export_options);
            $parent_record = $this->elementManager->invokeMethod('buildExportRecord', $elements[$parent_key], $webform_submission, $export_options);
            $parent_collection[$parent_key] = array_combine($parent_header, $parent_record);
          }
          // Now collect if given.
          if (array_key_exists($original_key, $parent_collection[$parent_key])) {
            $message_data[$new_key] = $parent_collection[$parent_key][$original_key];
          }
          continue;
        }
        else {
          // We don't know of anything else so skip.
          continue;
        }
        // If the output is an array of more than 1 item, then do not
        // override the key value.
        if (is_array($record) && count($record) > 1) {
          $rows = array_combine($header, $record);
          $message_data += (!$rows ? $record : $rows);
        }
        // Pop the scalar value from the array if it is an array.
        else {
          $message_data[$new_key] = is_array($record) ? reset($record) : $record;
        }
      }
    }

    // 2. Get metadata.
    if (!empty($settings[MessageMetadataHelper::FIELD])) {
      foreach ($settings[MessageMetadataHelper::FIELD] as $metadata) {
        $key = $metadata['key'] ?? NULL;
        $value = $metadata['value'] ?? NULL;
        if (!empty($key) && !empty($value)) {
          $message_data[$key] = $value;
        }
      }
    }

    // 3. Convert string values to expected data types / clean output, also
    // needed for CRM expected input as provided by WaterAid.
    $message_data_clean = $this->formatCleanOutput($message_data);

    // 4. Create a Message object.
    return (new AzureStorageQueueMessage())
      ->setId($webform_submission->id())
      ->setData($message_data_clean);
  }

  /**
   * Extracts field values based on ::formatRecordFieldDefinitionValue().
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A Webform Submission.
   * @param mixed[] $field_definition
   *   A Field Definition.
   * @param string $new_key
   *   New key.
   *
   * @return mixed[]|string|null
   *   A string or array.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *
   * @see \Drupal\webform\Plugin\WebformExporter\TabularBaseWebformExporter
   */
  protected function formatRecordFieldDefinitionValue(WebformSubmissionInterface $webform_submission, array $field_definition, string $new_key): array|string|null {

    $field_name = $field_definition['name'];
    $field_type = $field_definition['type'];

    // This seems pretty much pre-defined, so jus take it as it was written.
    // Add the new key as an empty array, as that will be flattened, and is
    // expected by CRM yet.
    // @see WaterAidWebformColumns::getArbitraryFieldDefinitions().
    if (str_starts_with($field_name, 'url_params_')) {
      $url_params = $this->getUrlParams($webform_submission);
      $url_param_field = str_replace('url_params_', '', $field_name);
      return array_key_exists($url_param_field, $url_params) === TRUE ? $url_params[$url_param_field] : NULL;
    }

    // Arbitrary field; Return the user name if a valid user uid is given.
    if ($field_name === 'uid_title') {
      $owner = $webform_submission->getOwner();
      return $owner->isAnonymous() ? NULL : $owner->getAccountName();
    }

    // Arbitrary field; Return the user URL if a valid user uid is given.
    if ($field_name === 'uid_url') {
      $owner = $webform_submission->getOwner();
      return $owner->isAnonymous() ? NULL : \Drupal::request()->getSchemeAndHttpHost() . '/user/' . $owner->id();
    }

    // The 'completed' field of type 'timestamp' needs to stay a timestamp for
    // it to interface with CRM.
    if ($field_name === 'completed') {
      return $webform_submission->$field_name->value;
    }

    // Generic field type return values.
    switch ($field_type) {
      case 'created':
      case 'changed':
      case 'timestamp':
        return $webform_submission->$field_name->value ? $this->dateFormatter->format($webform_submission->$field_name->value, 'custom', 'Y-m-d H:i:s') : '';

      case 'entity_reference':
        return $webform_submission->$field_name->target_id;

      case 'entity_url':
      case 'entity_title':
        $entity = $webform_submission->getSourceEntity(TRUE);
        if ($entity) {
          return $field_type === 'entity_url' && $entity->hasLinkTemplate('canonical')
            ? $entity->toUrl()->setOption('absolute', TRUE)->toString()
            : $entity->label();
        }
        return NULL;

      case 'map':
        return $webform_submission->$field_name->getValue();

      default:
        return $webform_submission->$field_name->value;
    }
  }

  /**
   * Maps the message values to expected data type values.
   *
   * @param mixed[] $input
   *   Dirty input.
   *
   * @return mixed[]
   *   Clean output.
   */
  protected function formatCleanOutput(array $input): array {
    array_walk($input, static function (&$value, $key): void {
      // Order of execution below is important!
      if ($value === '' || is_array($value)) {
        $value = NULL;
      }
      // Looks like when re-queued via VBO, the values are of different
      // data types...
      if ($value === '0' || $value === '1' || ctype_digit($value)) {
        // Never convert the following key values to an integer even if they
        // appear to be numeric so that leading zeroes are preserved.
        $string_value_keys = [
          'dd_account_number',
          'contact_phone',
        ];
        if (!in_array($key, $string_value_keys)) {
          $value = (int) $value;
        }
      }
    });
    return $input;
  }

  /**
   * Helper method to attain URL params from config.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A Webform Submission.
   *
   * @return mixed[]
   *   An array of URL params.
   */
  protected function getUrlParams(WebformSubmissionInterface $webform_submission): array {
    if ($this->urlParams === NULL) {
      $this->urlParams = WaterAidDelimitedExporter::getUrlParams($webform_submission);
    }
    return $this->urlParams;
  }

}
