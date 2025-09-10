<?php

namespace Drupal\wateraid_donation_report;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Donation Report Mail Service.
 */
class WaterAidDonationReportMailService {

  /**
   * Mail manager service.
   */
  protected MailManagerInterface $mailManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   *   The configuration factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * A logger instance.
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a new WaterAidDonationReportMailService object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   The mail manager interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    MailManagerInterface $mailManager,
    ConfigFactoryInterface $configFactory,
    LoggerInterface $logger,
  ) {
    $this->mailManager = $mailManager;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
  }

  /**
   * Sends the donation report email.
   *
   * @param mixed $file
   *   The file.
   */
  public function sendMail(mixed $file): void {
    if (!empty($file)) {
      $recipients = $this->configFactory->get('wateraid_donation_report.config')
        ->get('email_list');
      $base_url = $this->configFactory->get('wateraid_donation_report.config')
        ->get('base_url');

      if (!empty($recipients) && !empty($base_url)) {
        $recipients = implode(', ', $recipients);

        // Build email variables.
        $module = 'wateraid_donation_report';
        $key = 'donation_report_mail';
        $lang_code = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $url = Url::fromRoute('wateraid_donation_report.admin')->toString();
        $url = rtrim($base_url, '/') . $url;

        $params['body'] = "Hello,\n\n A new donation report has been generated.\n\n Please visit the donation report page or click the following link. \n\n" . $url;

        try {
          $this->mailManager->mail($module, $key, $recipients, $lang_code, $params, NULL, TRUE);
        }
        catch (\Exception $e) {
          $this->logger->error('Unable to send donation report email.');
        }
      }
    }
  }

}
