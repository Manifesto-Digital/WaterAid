<?php

namespace Drupal\wateraid_forms\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\Plugin\WebformElement\Checkboxes;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'flexible options' element.
 *
 * @WebformElement(
 *   id = "flexible_options",
 *   label = @Translation("Flexible options"),
 *   description = @Translation("Provides a form element for a set of options, with the additional functionality."),
 *   category = @Translation("Options elements"),
 * )
 */
class FlexibleOptions extends Checkboxes {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties(): array {
    return parent::getDefaultProperties() + [
      'flex_type' => 'checkboxes',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, ?WebformSubmissionInterface $webform_submission = NULL): void {
    parent::prepare($element, $webform_submission);

    // Force wrapper - @see OptionsBase::prepare().
    // Issue #2396145: Option #description_display for webform element fieldset
    // is not changing anything.
    // @see core/modules/system/templates/fieldset.html.twig
    $is_description_display = isset($element['#description_display']);
    $has_description = !empty($element['#description']);
    if ($is_description_display && $has_description) {
      $description = WebformElementHelper::convertToString($element['#description']);
      switch ($element['#description_display']) {
        case 'before':
          $element += ['#field_prefix' => ''];
          $element['#field_prefix'] = '<div class="description">' . $description . '</div>' . $element['#field_prefix'];
          unset($element['#description'], $element['#description_display']);
          break;

        case 'invisible':
          $element += ['#field_suffix' => ''];
          $element['#field_suffix'] .= '<div class="description visually-hidden">' . $description . '</div>';
          unset($element['#description'], $element['#description_display']);
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Checkboxes must require > 2 options.
    $form['options']['flex_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Selection type'),
      '#options' => [
        'checkboxes' => new TranslatableMarkup('Checkboxes'),
        'yes_no' => new TranslatableMarkup('Yes/no'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $options) {
    $flex_options = [
      'options_multiple_format' => 'separate',
      'options_item_format' => 'key',
      'header_prefix' => TRUE,
    ] + $options;
    return parent::buildExportHeader($element, $flex_options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options): array {
    $element_options = $element['#options'];

    $record = [];
    // Combine the values so that isset can be used instead of in_array().
    // http://stackoverflow.com/questions/13483219/what-is-faster-in-array-or-isset
    $deltas = FALSE;
    $values = $this->getValue($element, $webform_submission);
    if (is_array($values)) {
      $values = array_combine($values, $values);
      $deltas = $this->exportDelta ? array_flip(array_values($values)) : FALSE;
    }
    // Separate multiple values (ie options).
    foreach ($element_options as $option_value => $option_text) {
      if ((is_array($values) && isset($values[$option_value])) || $values == $option_value) {
        $record[$option_value] = $deltas ? $deltas[$option_value] + 1 : 'yes';
      }
      else {
        $record[$option_value] = 'no';
      }
    }
    return $record;
  }

  /**
   * Override output to include all options (not just those that were selected).
   *
   *  We need to explicitly say whether they were checked or not.
   *
   * @param mixed[] $element
   *   The element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   * @param mixed[] $options
   *   The options.
   *
   * @return mixed[]|string
   *   Render array or empty string on error.
   */
  protected function formatHtmlItems(array &$element, WebformSubmissionInterface $webform_submission, array $options = []): array|string {

    $format = $this->getItemsFormat($element);
    if (in_array($format, ['ol', 'ul'], TRUE) === FALSE) {
      return '';
    }

    $items = [];
    $value = $this->getValue($element, $webform_submission, $options);

    foreach ($element['#options'] as $option_key => $option_value) {
      $item_value = $option_value;
      if (isset($element['#format']) && $element['#format'] === 'raw') {
        $item_value = $option_key;
      }
      $suffix = '✗';
      if (!empty($value) && in_array($option_key, $value)) {
        $suffix = '✓';
      }
      $items[] = ['#plain_text' => "$item_value = $suffix"];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#list_type' => $format,
    ];
  }

  /**
   * Override format() so that we do output something when options are empty.
   *
   * @param string $type
   *   The type.
   * @param mixed[] $element
   *   The element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   * @param mixed[] $options
   *   The options.
   *
   * @return mixed[]|string
   *   Render array or empty string.
   */
  protected function format($type, array &$element, WebformSubmissionInterface $webform_submission, array $options = []): array|string {
    $item_function = 'format' . $type . 'Item';
    $items_function = 'format' . $type . 'Items';

    if ($this->hasMultipleValues($element)) {
      return $this->$items_function($element, $webform_submission, $options);
    }
    return $this->$item_function($element, $webform_submission, $options);
  }

}
