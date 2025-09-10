<?php

namespace Drupal\wateraid_base_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\user\Entity\User;

/**
 * Plugin implementation of the 'Authors' formatter.
 *
 * @FieldFormatter(
 *   id = "authors_formatter",
 *   label = @Translation("Authors list"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class AuthorsFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $count = $items->count();
    $counter = 0;
    $names = '';

    foreach ($this->getEntitiesToView($items, $langcode) as $entity) {
      if ($entity instanceof User) {
        $counter++;

        if (!empty($entity->get('field_real_name')->getValue())) {
          $name = $entity->get('field_real_name')->getValue()[0]['value'];
        }
        else {
          $name = $entity->getAccountName();
        }

        if ($counter == $count && !empty($names)) {
          $names .= ' and ';
        }
        else {
          if (!empty($names)) {
            $names .= ', ';
          }
        }
        $names .= $name;
      }
    }

    if (!empty($names)) {
      $elements[0] = [
        'names' => [
          '#plain_text' => $names,
        ],
      ];
    }

    return $elements;
  }

}
