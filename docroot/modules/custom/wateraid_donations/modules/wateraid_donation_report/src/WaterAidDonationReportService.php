<?php

declare(strict_types=1);

namespace Drupal\wateraid_donation_report;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file\FileInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\wateraid_forms\Plugin\WebformExporter\WaterAidDelimitedExporter;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;
use League\Csv\Exception;
use League\Csv\Writer;
use Psr\Log\LoggerInterface;

/**
 * Wateraid donation service.
 */
class WaterAidDonationReportService {

  /**
   * Name of the private directory.
   *
   * @var string
   */
  public const PRIVATE_DIR = 'private://donation_reports';

  /**
   * A logger instance.
   */
  protected LoggerInterface $logger;

  /**
   * The database connection.
   */
  protected Connection $database;

  /**
   * The file_system service.
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The stream wrapper manager.
   */
  protected StreamWrapperManagerInterface $streamWrapperManager;

  /**
   * The entity type manager interface.
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * The file repository service.
   */
  protected FileRepositoryInterface $fileRepository;

  /**
   * The wateraid dontion report mail service.
   */
  protected WaterAidDonationReportMailService $reportMailService;

  /**
   * The date formatter service.
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * WaterAidDonationReportService constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file_system service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManager $streamWrapperManager
   *   Drupal core stream wrapper.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\file\FileRepositoryInterface $fileRepository
   *   The file repository service.
   * @param \Drupal\wateraid_donation_report\WaterAidDonationReportMailService $reportMailService
   *   The wateraid report mail service.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   The date formatter service.
   */
  public function __construct(
    LoggerInterface $logger,
    Connection $database,
    FileSystemInterface $fileSystem,
    StreamWrapperManager $streamWrapperManager,
    EntityTypeManagerInterface $entityTypeManager,
    FileRepositoryInterface $fileRepository,
    WaterAidDonationReportMailService $reportMailService,
    DateFormatter $dateFormatter,
  ) {
    $this->logger = $logger;
    $this->database = $database;
    $this->fileSystem = $fileSystem;
    $this->streamWrapperManager = $streamWrapperManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->fileRepository = $fileRepository;
    $this->reportMailService = $reportMailService;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * Generate donation CSV file.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The timestamp to include data submitted after.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end
   *   The timestamp to include data submitted before.
   * @param bool $force
   *   Whether force generate an existing record. Defaults to FALSE.
   *
   * @return bool
   *   Whether the file was successfully saved or not.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \League\Csv\CannotInsertRecord
   * @throws \League\Csv\Exception
   */
  public function generateDonationCsv(DrupalDateTime $start, DrupalDateTime $end, bool $force = FALSE): bool {
    $directory = $this->streamWrapperManager
      ->getViaUri(self::PRIVATE_DIR)
      ->getUri();

    // Filename is set to use the first day of the prev month.
    // e.g. donation-report-2022-01.csv.
    $filename = 'donation-report-' . $start->format('Y-m') . '.csv';

    if (file_exists($directory . '/' . $filename)) {
      if ($force) {

        // If we have been told to force regenerate the file, we'll delete the
        // existing one and carry on as normal.
        $this->fileSystem->delete($directory . '/' . $filename);
      }
      else {

        // Else we don't need to generate the file.
        $this->logger->error('Report for ' . $start->format('Y-m') . ' already exists: try using force.');
        return FALSE;
      }
    }

    $submissions = $this->getDonationSubmissionData($start, $end);

    if (!empty($submissions)) {
      $csv = Writer::createFromString();

      // getHeader() returns a key->value pair array
      // we use the array keys to create the csv header row.
      $csv->insertOne(array_keys($this->getCsvDataArray()));

      foreach ($submissions as $submission) {
        // getHeader() returns a key->value pair array
        // we use the array values to create the csv data row.
        $row = array_values($this->getCsvDataArray($submission));

        if (!empty($row)) {
          $csv->insertOne($row);
        }

      }

      return $this->saveReport($csv, $filename, $directory);
    }
    else {
      $this->logger->error('No submissions for the requested period.');
    }

    return FALSE;
  }

  /**
   * Returns donation submission entities.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The timestamp to include data submitted after.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end
   *   The timestamp to include data submitted before.
   *
   * @return \Drupal\webform\Entity\WebformSubmission[]
   *   An array of WebformSubmissions between the dates.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDonationSubmissionData(DrupalDateTime $start, DrupalDateTime $end): array {
    // Get the current open donation webforms.
    $query = $this->entityTypeManager->getStorage('webform')->getQuery()
      ->condition('status', 'open')
      ->condition('categories.*', 'donation');
    $donation_forms = $query->execute();

    // If we don't have any donation forms available, we don't want to load all
    // webform submissions, or trigger an exception by providing an empty "in"
    // condition. So we'll check it's set and return an empty results array if
    // not.
    if ($donation_forms) {
      // Search for any webform submissions between the first and last day of
      // the previous month.
      $query = $this->database
        ->select('webform_submission', 'ws')
        ->fields('ws', ['sid'])
        ->condition('ws.webform_id', $donation_forms, 'IN')
        ->condition('ws.created', [$start->getTimestamp(), $end->getTimestamp()], 'BETWEEN')
        ->execute();

      $results = $query->fetchAllKeyed(0, 0);
    }
    else {
      $results = [];
    }

    if (!empty($results)) {
      return WebformSubmission::loadMultiple($results);
    }

    return [];
  }

  /**
   * Saves and stores a CSV file entity.
   *
   * @param \League\Csv\Writer $csv
   *   A Writer object containing the CSV values.
   * @param string $filename
   *   The name of the file to be saved.
   * @param string $directory
   *   The directory to save the file to.
   *
   * @return bool
   *   Whether the file was successfully saved or not.
   */
  public function saveReport(Writer $csv, string $filename, string $directory): bool {
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    try {
      $report_uri = $this->fileSystem->saveData($csv->toString(), $directory . '/' . $filename, FileSystemInterface::EXISTS_REPLACE);
    }
    catch (Exception $e) {
      $this->logger->error('Unable to convert CSV data to string for donation report file: ' . $filename);
      return FALSE;
    }

    try {
      $file_storage = $this->entityTypeManager->getStorage('file');
    }
    catch (\Exception $e) {
      $this->logger->error('Unable to access file storage when creating donation report.');
      return FALSE;
    }

    // Try to load an existing file entity for this URI, otherwise create one.
    /** @var \Drupal\file\FileInterface $file */
    $file = $file_storage->loadByProperties(['uri' => $report_uri]);
    if ($file) {
      $file[0]->set('status', TRUE);
    }
    else {
      $file = $file_storage->create([
        'uri' => $report_uri,
        'status' => TRUE,
      ]);
    }

    try {
      $file->save();
      $this->reportMailService->sendMail($file);
    }
    catch (\Exception $e) {
      $this->logger->error('Unable to save donation report file ' . $file->getFilename());
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns an array of file entities for reporting purposes.
   *
   * @param string|null $date
   *   The search date.
   *
   * @return \Drupal\file\FileInterface[]
   *   An array of File entities for donation reports.
   */
  public function getReportFileEntities(?string $date = NULL): array {
    $entities = [];
    $dir = $this->streamWrapperManager->getViaUri(self::PRIVATE_DIR)->getUri();

    // If the directory doesn't exist, create it before scanning it.
    if (!$this->fileSystem->prepareDirectory($dir)) {
      $this->fileSystem->mkdir($dir, NULL, TRUE);
    }

    $files = $this->fileSystem->scanDirectory($dir, '/.csv/');
    $filenames = array_column($files, 'uri');

    try {
      $entities = $this->entityTypeManager->getStorage('file')->loadByProperties(['uri' => $filenames]);
    }
    catch (\Exception $e) {
      $this->logger->error('Unable to load files for Donation Reports.');
    }

    if (!empty($date)) {
      $results = [];
      foreach ($entities as $key => $entity) {
        if (strpos($entity->getFilename(), $date)) {

          $results[$key] = $entity;
        }
      }

      $entities = $results;
    }

    return $entities;
  }

  /**
   * Returns a CSV header/data array.
   *
   * @param \Drupal\webform\WebformSubmissionInterface|null $submission
   *   Webform submission.
   *
   * @return string[]
   *   Returns an array of data.
   */
  public function getCsvDataArray(?WebformSubmissionInterface $submission = NULL): array {
    // Set up default array with empty values to ensure all CSV columns exist.
    $output = [
      'Serial number' => '',
      'Submission ID' => '',
      'Created' => '',
      'Communication preferences: opt_in_email' => '',
      'Communication preferences: opt_in_phone' => '',
      'Communication preferences: opt_in_sms' => '',
      'Communication preferences: opt_in_post' => '',
      'Reason for donating' => '',
      'donation_currency' => '',
      'donation_amount' => '',
      'donation_transaction_id' => '',
      'dd_amount' => '',
      'utm_campaign' => '',
      'utm_source' => '',
      'utm_content' => '',
      'utm_medium' => '',
      'fund_code' => '',
      'package_id' => '',
      'campaign' => '',
      'segment_code' => '',
    ];

    // If there's no submission, return the default.
    if (!$submission) {
      return $output;
    }

    // Get the converted URL params.
    $url_params = WaterAidDelimitedExporter::getUrlParams($submission);

    // Add submission data.
    $output['Serial number'] = $submission->serial();
    $output['Submission ID'] = $submission->id();
    $output['Created'] = $this->dateFormatter->format($submission->getCreatedTime(), 'wateraid-medium');

    // Add data values if present.
    $data = $submission->getData();

    if (empty($data['prompt_reason'])) {
      return [];
    }

    $output['Reason for donating'] = $data['prompt_reason'];
    $output['donation_amount'] = $data['donation__amount'] ?? '';
    $output['donation_transaction_id'] = $data['donation__transaction_id'] ?? '';
    $output['package_id'] = $data['donation__package_code'] ?? '';

    // Direct Debit amount is only valid if the frequency is set to recurring.
    if (!empty($data['donation__frequency']) && $data['donation__frequency'] === 'recurring') {
      $output['dd_amount'] = $data['donation__amount'] ?? '';
    }

    // Add payment data if it exists.
    if (!empty($data['payment'])) {
      $output['donation_currency'] = $data['payment']['currency'] ?? '';
      $output['fund_code'] = $data['payment']['fundcode'] ?? '';
    }

    // Add communication preferences if the data exists.
    $preferences = $data['communication_preferences'] ?? NULL;
    if ($preferences) {
      $output['Communication preferences: opt_in_email'] = $this->getPreference('opt_in_email', $preferences);
      $output['Communication preferences: opt_in_phone'] = $this->getPreference('opt_in_phone', $preferences);
      $output['Communication preferences: opt_in_sms'] = $this->getPreference('opt_in_sms', $preferences);
      $output['Communication preferences: opt_in_post'] = $this->getPreference('opt_in_post', $preferences);
    }

    // Add URL parameter data if present.
    if (!empty($url_params)) {
      // Some URL params have a hidden space which need to be removed.
      foreach ($url_params as $key => $param) {
        $string = htmlentities($param);
        $param = str_replace("&nbsp;", "", $string);
        $param = html_entity_decode($param);

        $converted_params[$key] = trim($param);
      }

      $output['utm_campaign'] = $converted_params['utm_campaign'] ?? '';
      $output['utm_source'] = $converted_params['utm_source'] ?? '';
      $output['utm_content'] = $converted_params['utm_content'] ?? '';
      $output['utm_medium'] = $converted_params['utm_medium'] ?? '';
      $output['campaign'] = $converted_params['campaign'] ?? '';
      $output['segment_code'] = $converted_params['segment_code'] ?? '';
    }

    return $output;
  }

  /**
   * Function to archive the file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   */
  public function archiveFile(FileInterface $file): void {
    $dir = $this->streamWrapperManager
      ->getViaUri(self::PRIVATE_DIR)
      ->getUri();

    // Append archived to the filename.
    $filename = 'archived-' . $file->getFilename();

    try {
      // Replace the file with an appended 'archived' filename.
      $this->fileRepository->move($file, $dir . '/' . $filename);

      // Update the filename and Uri.
      $file->setFilename($filename);
      $file->setFileUri(self::PRIVATE_DIR . '/' . $filename);
      $file->save();
    }
    catch (\Exception $e) {
      $this->logger->info('Unable to move' . $file->getFilename() . 'to the archived directory');
    }
  }

  /**
   * Helper function to return accepted preference.
   *
   * @param string $type
   *   The accepted preference type.
   * @param mixed[] $data
   *   The data array.
   *
   * @return mixed
   *   The preference or an empty string.
   */
  public function getPreference(string $type, array $data): mixed {
    return in_array($type, $data, TRUE) ? $type : '';
  }

}
