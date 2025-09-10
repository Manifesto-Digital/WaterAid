<?php

namespace Drupal\wateraid_forms\Plugin\WebformElement;

use Drupal\wateraid_forms\Element\WebformName as WebformNameElement;
use Drupal\webform\Plugin\WebformElement\WebformName as CoreWebformName;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a WaterAid 'name' element.
 *
 * @WebformElement(
 *   id = "wateraid_forms_webform_name",
 *   label = @Translation("Name (WaterAid)"),
 *   category = @Translation("WaterAid"),
 *   description = @Translation("Provides a WaterAid compliant form element to collect a person's full name."),
 *   multiline = TRUE,
 *   composite = TRUE,
 * )
 */
class WebformName extends CoreWebformName {

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements(): array {
    return WebformNameElement::getCompositeElements([]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options): array {
    $value = $this->getValue($element, $webform_submission);

    $fields = ['title', 'first', 'last'];
    $export_record = [];
    foreach ($fields as $field) {
      $field_access_key = "#{$field}__access";
      // Check access to composite field element.
      if (isset($element[$field_access_key]) && $element[$field_access_key] === FALSE) {
        continue;
      }
      // Capture value if access is granted and if given.
      $export_record[] = $value[$field] ?? '';
    }

    return $export_record;
  }

  /**
   * Override formatHtmlItem, remove the #suffix as it will contain a <br/>.
   *
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array|string|null {
    $lines = parent::formatHtmlItem($element, $webform_submission, $options);
    if (isset($lines['name']['#suffix'])) {
      unset($lines['name']['#suffix']);
    }
    return $lines;
  }

}
