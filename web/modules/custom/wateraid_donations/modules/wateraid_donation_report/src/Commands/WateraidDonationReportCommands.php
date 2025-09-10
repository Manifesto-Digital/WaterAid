<?php

namespace Drupal\wateraid_donation_report\Commands;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\wateraid_donation_report\WaterAidDonationReportService;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class WateraidDonationReportCommands extends DrushCommands {

  /**
   * Wateraid donation report service.
   *
   * @var \Drupal\wateraid_donation_report\WaterAidDonationReportService
   *  The wateraid donation service.
   */
  protected WaterAidDonationReportService $reportService;

  /**
   * Wateraid DonationReport Commands constructor.
   *
   * @param \Drupal\wateraid_donation_report\WaterAidDonationReportService $report_service
   *   The Wateraid Donation Report service.
   */
  public function __construct(WaterAidDonationReportService $report_service) {
    $this->reportService = $report_service;
  }

  /**
   * Generate a donation submission report file for the given month.
   *
   * @param int $year
   *   The year to generate a file for.
   * @param int $month
   *   The month to generate a file for.
   * @param int $force
   *   Whether to force regeneration of the file.
   *
   * @usage wateraid_donation_report:generate 2023 12 1
   *    generate <year> <month> <force: defaults to 0>
   *
   * @command wateraid_donation_report:generate
   * @aliases wdrg
   *
   * @throws \League\Csv\CannotInsertRecord
   * @throws \League\Csv\Exception
   */
  public function generate(int $year, int $month, int $force = 0): void {
    $error = FALSE;

    // Cast the int as a bool for the service function.
    $force = (bool) $force;
    if ($month > 12 || $month < 1) {
      $this->logger()->error('Month must be 1 - 12.');
      $error = TRUE;
    }
    if ($year > date('Y')) {
      $this->logger()->error('Year must be the current year or in the past.');
      $error = TRUE;
    }
    if ($year == date('Y') && $month > date('m')) {
      $this->logger()->error('Month must be the current month or in the past for the current year.');
      $error = TRUE;
    }

    if ($error) {
      $this->logger()->error(dt('Unable to create report file: error with parameters.'));
      return;
    }

    $start = new DrupalDateTime($year . '-' . $month . '-1');
    $start->setTime(0, 0);
    $end = new DrupalDateTime($year . '-' . ($month + 1) . '-1');
    $end->sub(\DateInterval::createFromDateString('1 day'));
    $end->setTime(23, 59, 59);

    $success = $this->reportService->generateDonationCsv($start, $end, $force);
    if ($success) {
      $this->logger()->success(dt('File successfully created for ' . $start->format('Y-m')));
      return;
    }

    $this->logger()->error(dt('Unable to create report file.'));
  }

}
