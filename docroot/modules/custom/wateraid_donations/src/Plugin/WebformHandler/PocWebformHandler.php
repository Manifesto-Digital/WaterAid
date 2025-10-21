<?php

namespace Drupal\wateraid_donations\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Displays settings.
 *
 * @WebformHandler(
 *   id = "poc_webform",
 *   label = @Translation("POC Webform Handler"),
 *   category = @Translation("Other"),
 *   description = @Translation("Demonstrates getting data from a parent node."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class PocWebformHandler extends WebformHandlerBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function alterElements(array &$elements, WebformInterface $webform): void {
    $parent = $this->getParent();

    if ($parent) {
      $webform->addCacheableDependency($parent);

      if ($parent->hasField('field_options')) {
        $options = $parent->get('field_options')->getString();

        if ($options && isset($elements['options']['#options'])) {
          $new = [];
          foreach (explode(', ', $options) as $option) {
            $new[$option] = $elements['options']['#options'][$option];
          }

          if (!empty($new)) {
            $elements['options']['#options'] = $new;
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission): void {
    if ($parent = $this->getParent()) {
      \Drupal::messenger()->addStatus(t('Can collect settings from :entity_type :entity (:id)', [
        ':entity_type' => $parent->getEntityType()->getLabel(),
        ':entity' => $parent->label(),
        ':id' => $parent->id(),
      ]));
    }
  }

  /**
   * Helper to get the entity a webform is attached to.
   *
   * @return \Drupal\node\NodeInterface|\Drupal\paragraphs\ParagraphInterface|null
   */
  private function getParent(): NodeInterface|ParagraphInterface|null {
    $parent = NULL;

    if ($this->webform) {
      $request_handler = \Drupal::service('webform.request');

      /** @var \Drupal\node\NodeInterface $node */
      if ($node = $request_handler->getCurrentSourceEntity('webform')) {
        if ($node->hasField('webform')) {
          foreach ($node->get('webform')->getValue() as $values) {
            if ($values['target_id'] == $this->webform->id()) {
              $parent = $node;
            }
          }
        }

        if (!$parent) {
          if ($node->hasField('field_content')) {
            foreach ($node->get('field_content')->referencedEntities() as $paragraph) {
              if ($paragraph->bundle() == 'webform_poc') {
                $form_candidate = $paragraph->get('field_webform')->referencedEntities();

                if (isset($form_candidate[0])) {
                  if ($form_candidate[0]->id() == $this->webform->id()) {
                    $parent = $paragraph;
                  }
                }
              }
            }
          }
        }
      }
    }

    return $parent;
  }
}
