<?php

namespace Drupal\wateraid_donation_report\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\Core\Url;
use Drupal\wateraid_donation_report\WaterAidDonationReportService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements an example form.
 */
class DonationReportForm extends FormBase {

  /**
   * The WaterAid Donation Report service.
   */
  protected WaterAidDonationReportService $reportService;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);

    $instance->reportService = $container->get('wateraid_donation_report.service');
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'donation_report_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $date = NULL): array {
    $form['date'] = [
      '#title' => 'Date',
      '#type' => 'date',
      '#default_value' => $date,
      '#date_date_format' => 'm/Y',
      '#attributes' => ['type' => 'month'],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#submit' => ['::submitForm'],
      '#button_type' => 'primary',
    ];

    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#button_type' => 'primary',
      '#submit' => ['::submitFormReset'],
    ];

    $form['table'] = [
      '#type' => 'table',
      '#header' => $this->getTableHeader(),
      '#rows' => $this->getTableData($this->routeMatch->getParameter('date')),
      '#empty' => $this->t('There are no reports to show.'),
      '#weight' => 100,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if (!empty($form_state->getValue('date'))) {
      $form_state->setRedirect('wateraid_donation_report.admin', ['date' => $form_state->getValue('date')]);
    }
    else {
      $form_state->setRedirect('wateraid_donation_report.admin');
    }
  }

  /**
   * Resets the form to show all files.
   *
   * @param mixed[] $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form State.
   */
  public function submitFormReset(array &$form, FormStateInterface $form_state): void {
    $form_state->setRedirect('wateraid_donation_report.admin');
  }

  /**
   * Returns table header.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The table header.
   */
  public function getTableHeader(): array {
    return [
      'filename' => $this->t('File name'),
      'filetype' => $this->t('File type'),
      'filesize' => $this->t('File size'),
      'download' => $this->t('Download'),
    ];
  }

  /**
   * Returns file entity data for the table.
   *
   * @param string|null $date
   *   The date string to obtain data for.
   *
   * @return mixed[]
   *   The file data.
   */
  public function getTableData(?string $date = NULL): array {
    $rows = [];
    $files = $this->reportService->getReportFileEntities($date);

    foreach ($files as $file) {
      // Check if the file is more than 12 months old.
      if ($file->getCreatedTime() <= strtotime("-1 year")) {
        $this->reportService->archiveFile($file);

        continue;
      }

      $filename = $file->getFilename();
      $link = Link::fromTextAndUrl('Download', Url::fromRoute('wateraid_donation_report.download', ['filename' => $filename]));

      $row = [
        'filename' => $filename,
        'filetype' => $file->getMimeType(),
        'filesize' => ByteSizeMarkup::create($file->getSize()),
        'download' => $link,
      ];

      $rows[] = $row;

    }

    // Sort the array ascending filename.
    usort($rows, static function ($a, $b) {
      return $a['filename'] <=> $b['filename'];
    });

    return $rows;
  }

}
