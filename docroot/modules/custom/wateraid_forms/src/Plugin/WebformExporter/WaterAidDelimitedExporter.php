<?php

namespace Drupal\wateraid_forms\Plugin\WebformExporter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\Plugin\WebformExporter\TabularBaseWebformExporter;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines csv exporter used to build WaterAid export files.
 *
 * @WebformExporter(
 *   id = "wateraid_csv",
 *   label = @Translation("WaterAid CSV export"),
 *   description = @Translation("Exports WaterAid form data as a csv file."),
 * )
 */
class WaterAidDelimitedExporter extends TabularBaseWebformExporter {

  /**
   * Save index of url_param.
   */
  protected int|false $urlParamIndex = FALSE;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $default_config = parent::defaultConfiguration();

    // Override defaults with WaterAid defaults.
    $default_config['header_format'] = 'label';
    $default_config['header_prefix_key_delimiter'] = '__';
    $default_config['header_prefix'] = FALSE;
    $default_config['delimiter'] = ',';
    return $default_config;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): static {
    parent::setConfiguration($configuration);
    $this->configuration = $configuration;
    $this->configuration['header_format'] = 'label';
    $this->configuration['header_prefix_key_delimiter'] = '__';
    $this->configuration['header_prefix'] = FALSE;
    $this->configuration['delimiter'] = ',';
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileExtension(): string {
    return 'csv';
  }

  /**
   * {@inheritdoc}
   */
  public function writeHeader(): void {
    $header = $this->buildHeader();

    $this->urlParamIndex = FALSE;

    // Remove the url_params field and add individual params.
    $url_params_key = array_search('url_params', $header);
    if ($url_params_key !== FALSE) {
      $this->urlParamIndex = $url_params_key;
      unset($header[$url_params_key]);
    }

    $url_parameters = _wateraid_forms_get_url_parameters();
    foreach ($url_parameters as $key) {
      if ($key === 'id') {
        $header[] = 'fund_code';
        $header[] = 'package_id';
        $header[] = 'campaign';
        $header[] = 'segment_code';
      }
      else {
        $header[] = $key;
      }
    }

    fputcsv($this->fileHandle, $header, $this->configuration['delimiter']);
  }

  /**
   * {@inheritdoc}
   */
  public function writeSubmission(WebformSubmissionInterface $webform_submission): void {
    $record = $this->buildRecord($webform_submission);

    if ($this->urlParamIndex !== FALSE) {
      unset($record[$this->urlParamIndex]);
    }

    $record += self::getUrlParams($webform_submission);

    // Tidy up any missing/erroneous data.
    foreach ($record as $key => $value) {
      // Check for translatable and convert to string.
      if ($value instanceof TranslatableMarkup) {
        $record[$key] = $value->render();
      }
      // Check for empty arrays and convert to string.
      if (is_array($value) && empty($value)) {
        $record[$key] = '';
      }
    }

    fputcsv($this->fileHandle, $record, $this->configuration['delimiter']);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatRecordFieldDefinitionValue(array &$record, WebformSubmissionInterface $webform_submission, array $field_definition): void {
    // Override export format for "timestamp" field type, because CRM requires
    // this to be a timestamp and not a formatted date string.
    $field_name = $field_definition['name'];
    $field_type = $field_definition['type'];
    if ($field_type === 'timestamp') {
      $record[] = $webform_submission->get($field_name)->value;
    }
    else {
      // For all other cases use default export logic.
      parent::formatRecordFieldDefinitionValue($record, $webform_submission, $field_definition);
    }
  }

  /**
   * Helper method to assemble URL params.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A Webform Submission.
   *
   * @return mixed[]
   *   An array of URL params.
   */
  public static function getUrlParams(WebformSubmissionInterface $webform_submission): array {
    $record = [];
    $url_params = $webform_submission->get('url_params')->getValue()[0] ?? [];
    $url_parameters = _wateraid_forms_get_url_parameters();
    foreach ($url_parameters as $key) {
      if ($key === 'id') {
        if (!empty($url_params['id'])) {
          // Parse id e.g. 17/JMB/01B.
          $id_array = explode(',', $url_params['id']);
          // If only one parameter in url, then it's the segment code.
          if (count($id_array) === 1) {
            $record['fund_code'] = '';
            $record['package_id'] = '';
            $record['campaign'] = '';
            $record['segment_code'] = $id_array[0];
          }
          else {
            $record['fund_code'] = $id_array[0] ?? '';
            $record['package_id'] = $id_array[1] ?? '';
            $record['campaign'] = $id_array[2] ?? '';
            $record['segment_code'] = $id_array[3] ?? '';
          }
        }
        // If URL does not contain parameter then default fund code
        // and  package code will be added to submission report.
        else {
          $data = $webform_submission->getData();

          if (is_array($data) && isset($data['payment'])) {
            $record['fund_code'] = $data['payment']['fund_code'] ?? '';
            $record['package_id'] = $data['payment']['package_code'] ?? '';
          }
          else {
            $record['fund_code'] = '';
            $record['package_id'] = '';
          }
        }
      }
      else {
        $record[$key] = !empty($url_params[$key]) ? $url_params[$key] : '';
      }
    }

    return $record;
  }

}
