<?php

namespace Drupal\wateraid_donation_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\wateraid_donation_report\WaterAidDonationReportService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Provides a report of donation submission files for download.
 */
class ReportController extends ControllerBase {

  /**
   * The WaterAid Donation Report service.
   */
  protected WaterAidDonationReportService $reportService;

  /**
   * Report controller constructor.
   */
  public function __construct(WaterAidDonationReportService $reportService) {
    $this->reportService = $reportService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('wateraid_donation_report.service'),
    );
  }

  /**
   * Download the CSV file.
   *
   * @param string $filename
   *   The filename.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   Response.
   */
  public function downloadCsv(string $filename): BinaryFileResponse {
    $directory = WaterAidDonationReportService::PRIVATE_DIR;

    $headers = [
      'Content-Type' => 'text/csv',
      'Content-Description' => 'File Download',
      'Content-Disposition' => 'attachment; filename=' . $filename,
    ];

    return new BinaryFileResponse($directory . '/' . $filename, 200, $headers, TRUE);
  }

}
