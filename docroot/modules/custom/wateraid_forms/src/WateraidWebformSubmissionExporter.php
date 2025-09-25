<?php

namespace Drupal\wateraid_forms;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionExporter;

/**
 * Wateraid Webform Submission Exporter.
 *
 * @package Drupal\wateraid_forms
 */
class WateraidWebformSubmissionExporter extends WebformSubmissionExporter {

  /**
   * {@inheritdoc}
   */
  public function getDefaultExportOptions(): array {
    if (isset($this->defaultOptions)) {
      return $this->defaultOptions;
    }

    $this->defaultOptions = parent::getDefaultExportOptions();

    // Make sure we have the correct settings.
    $this->defaultOptions['exporter'] = 'wateraid_csv';
    $this->defaultOptions['header_format'] = 'key';
    $this->defaultOptions['header_prefix_key_delimiter'] = '_';
    $this->defaultOptions['header_prefix'] = FALSE;
    $this->defaultOptions['excluded_columns'] = [
      'uuid' => 'uuid',
      'token' => 'token',
      'webform_id' => 'webform_id',
    ];
    $this->defaultOptions['options_format'] = 'key';
    $this->defaultOptions['options_item_format'] = 'separate';

    return $this->defaultOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options = []): void {
    $class = get_class($this);

    parent::buildExportOptionsForm($form, $form_state, $export_options);

    $form['export']['columns']['excluded_columns']['#after_build'][] = [
      $class,
      'afterBuildWebformExcluded',
    ];

    $form['export']['header']['#access'] = FALSE;
    $form['export']['element']['#access'] = FALSE;

    // Originally, all the elements options were also hidden, but Loqate
    // configuration fields need to be public.
    foreach ($form['export']['elements'] as $id => &$export_element) {
      if (!is_array($export_element)) {
        continue;
      }

      $export_element['#access'] = FALSE;
      if (in_array($id, ['loqate', 'capture_plus'])) {
        $export_element['#access'] = TRUE;
      }
    }
  }

  /**
   * Processes a webform elements webform element.
   *
   * @param mixed[] $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed[]
   *   The element.
   */
  public static function afterBuildWebformExcluded(array $element, FormStateInterface $form_state): array {
    // @todo Limit selection of column options - or use access to do this.
    return $element;
  }

}
