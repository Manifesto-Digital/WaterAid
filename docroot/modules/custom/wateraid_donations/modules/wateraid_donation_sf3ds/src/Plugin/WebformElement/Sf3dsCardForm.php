<?php

namespace Drupal\wateraid_donation_sf3ds\Plugin\WebformElement;

use Drupal\wateraid_donation_sf3ds\Element\Sf3dsCardForm as sf3dsElement;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'sf3ds_card_form' element.
 *
 * @WebformElement(
 *   id = "sf3ds_card_form",
 *   label = @Translation("SF3DS Card Form"),
 *   category = @Translation("WaterAid Donations"),
 *   description = @Translation("Provides a form element to input sf3ds card details"),
 *   multiline = FALSE,
 *   composite = TRUE,
 *   states_wrapper = FALSE,
 * )
 */
class Sf3dsCardForm extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements(): array {
    return sf3dsElement::getCompositeElements([]);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission): void {
    $data = $webform_submission->getData();
    $element_key = $element['#webform_key'];

    // Don't store any card information.
    if (isset($data[$element_key])) {
      $data[$element_key] = [];
      $webform_submission->setData($data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $options): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options): array {
    return [];
  }

}
