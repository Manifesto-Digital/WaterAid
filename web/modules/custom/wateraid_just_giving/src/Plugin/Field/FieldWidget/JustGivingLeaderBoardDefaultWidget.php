<?php

namespace Drupal\wateraid_just_giving\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'default' widget.
 *
 * @FieldWidget(
 *   id = "just_giving_leaderboard_default_widget",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "just_giving_leaderboard"
 *   }
 * )
 */
class JustGivingLeaderBoardDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {

    $element += [
      '#type' => 'fieldset',
    ];

    // Charity version not supported yet.
    $element['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Leaderboard type'),
      '#default_value' => $items[$delta]->type ?? NULL,
      '#options' => [
        'event' => $this->t('Event'),
      ],
    ];

    $element['just_giving_id'] = [
      '#type' => 'textfield',
      '#size' => 20,
      '#title' => $this->t('JustGiving Id'),
      '#autocomplete_route_name' => 'just_giving.search_autocomplete',
      '#autocomplete_route_parameters' => [
        'search_type' => 'event',
        'field_name' => 'event_id',
        'count' => 10,
      ],
      '#default_value' => $items[$delta]->just_giving_id ?? NULL,
    ];

    return $element;
  }

}
