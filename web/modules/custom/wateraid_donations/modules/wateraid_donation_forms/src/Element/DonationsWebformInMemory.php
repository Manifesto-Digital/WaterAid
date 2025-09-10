<?php

namespace Drupal\wateraid_donation_forms\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a Webform element for In Memory donations.
 *
 * @FormElement("donations_webform_in_memory")
 *
 * @see \Drupal\wateraid_donation_forms\Plugin\WebformElement\DonationsWebformInMemory
 */
class DonationsWebformInMemory extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $info = parent::getInfo();
    unset($info['#theme']);
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $elements = [];

    // We can't use "webform_name" as field type here as per the exception:
    // "Nested composite elements are not supported within composite elements".
    $elements['in_memory_title'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Title'),
      '#options' => 'titles',
    ];
    $elements['in_memory_firstname'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('First Name'),
      '#prefix' => '<div class="' . HTML::cleanCssIdentifier('wa-subelement-wrapper-name') . '">',
    ];
    $elements['in_memory_lastname'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Last Name'),
      '#suffix' => '</div>',
    ];

    // See config file "webform.webform_options.relationship_in_memory.yml".
    $elements['in_memory_relationship'] = [
      '#type' => 'select',
      '#title' => new TranslatableMarkup('Relationship'),
      '#options' => 'relationship_in_memory',
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\wateraid_donation_forms\Plugin\WebformElement\DonationsWebformInMemory::form()
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): array {
    parent::processWebformComposite($element, $form_state, $complete_form);

    $markup = $element['#in_memory_intro'] ?? NULL;

    $element['in_memory_intro'] = [
      '#type' => 'webform_markup',
      '#markup' => $markup ? '<div class="form-item">' . $markup . '</div>' : NULL,
      '#weight' => -100,
    ];

    return $element;
  }

}
