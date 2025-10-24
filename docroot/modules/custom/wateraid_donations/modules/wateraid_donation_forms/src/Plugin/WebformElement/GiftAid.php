<?php

namespace Drupal\wateraid_donation_forms\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element as RenderElement;
use Drupal\wateraid_donation_forms\Element\GiftAid as GiftAidElement;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provide a Stripe single card webform element.
 *
 * @WebformElement(
 *  id = "gift_aid",
 *  label = @Translation("Gift aid"),
 *  description = @Translation("Provides a Gift aid radio button element"),
 *  category = @Translation("WaterAid"),
 *  composite = TRUE,
 * )
 */
class GiftAid extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements(): array {
    return GiftAidElement::getCompositeElements([]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $options): array {
    if (!empty($element['#multiple'])) {
      return parent::buildExportHeader($element, $options);
    }

    $composite_elements = $this->getInitializedCompositeElement($element);
    $header = [];
    foreach (RenderElement::children($composite_elements) as $composite_key) {
      $composite_element = $composite_elements[$composite_key];
      if (isset($composite_element['#access']) && $composite_element['#access'] === FALSE) {
        continue;
      }
      $header[] = $composite_key;
    }

    $header = array_intersect($header, ['opt_in', 'date_made']);

    return $this->prefixExportHeader($header, $element, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options): array {
    $value = $this->getValue($element, $webform_submission);
    return [
      $value['opt_in'] ?? '',
      $value['date_made'] ?? '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['composite']['elements']['date_made']['#access'] = FALSE;
    $form['composite']['elements']['opt_in']['title_and_description']['data']['opt_in__placeholder']['#access'] = FALSE;

    return $form;
  }

}
