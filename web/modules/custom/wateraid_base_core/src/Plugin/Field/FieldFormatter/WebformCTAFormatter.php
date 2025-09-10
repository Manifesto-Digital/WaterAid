<?php

namespace Drupal\wateraid_base_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Plugin\Field\FieldFormatter\WebformEntityReferenceEntityFormatter;
use Drupal\webform\WebformMessageManagerInterface;

/**
 * Plugin implementation of the 'webform_cta_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "webform_cta_formatter",
 *   label = @Translation("Webform cta formatter"),
 *   field_types = {
 *     "webform"
 *   }
 * )
 */
class WebformCTAFormatter extends WebformEntityReferenceEntityFormatter {

  /**
   * Override viewElements().
   *
   *  We do _almost_ the same as parent, except we set the entity_type for the
   *  form submissions values to that of the webform, and not the source_entity.
   *  We also hide the progress bar.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Field items.
   * @param mixed $langcode
   *   The langcode.
   *
   * @return mixed[]
   *   An elements array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function viewElements(FieldItemListInterface $items, mixed $langcode): array {
    $source_entity = $items->getEntity();
    $this->messageManager->setSourceEntity($source_entity);

    $elements = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // Do not display the webform if the current user can't create
      // submissions.
      if ($entity->id() && !$entity->access('submission_create')) {
        continue;
      }

      if ($entity->isOpen()) {
        $values = [
          'entity_type' => $entity->getEntityTypeId(),
          'entity_id' => NULL,
        ];
        if (!empty($items[$delta]->default_data)) {
          $values['data'] = Yaml::decode($items[$delta]->default_data);
        }

        // Indicate that the form is embedded within a CTA widget.
        $entity->set('is_cta_widget', TRUE);

        // Build the Webform.
        $elements[$delta] = $entity->getSubmissionForm($values);
        hide($elements[$delta]['progress']);

        /*
         * Identify the most recently authored, published Webform Node
         * for this Webform.
         */
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $webform_nodes = $node_storage->getQuery()
          ->condition('type', 'webform')
          ->condition('status', '1')
          ->condition('webform', $entity->id())
          ->sort('created', 'DESC')
          ->range(0, 1)
          ->accessCheck()
          ->execute();

        if ($webform_nodes) {
          // A published Webform Node exists.
          $nid = reset($webform_nodes);
          $node = $node_storage->load($nid);

          // On submit, redirect to the Webform Node.
          $elements[$delta]['#action'] = $node->toUrl()->toString();
        }
        else {
          // On submit, redirect to the Webform canonical route.
          $elements[$delta]['#action'] = $entity->toUrl()->toString();
        }

        $elements[$delta]['#method'] = 'GET';
        $elements[$delta]['#attributes']['class'][] = 'wateraid-donations-is-cta-form';
      }
      else {
        $this->messageManager->setWebform($entity);
        $message_type = $entity->isOpening() ? WebformMessageManagerInterface::FORM_OPEN_MESSAGE : WebformMessageManagerInterface::FORM_CLOSE_MESSAGE;
        $elements[$delta] = $this->messageManager->build($message_type);
      }

      $this->setCacheContext($elements[$delta], $entity, $items[$delta]);
    }
    return $elements;
  }

}
