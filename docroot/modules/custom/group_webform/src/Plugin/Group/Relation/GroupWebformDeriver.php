<?php

namespace Drupal\group_webform\Plugin\Group\Relation;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;

/**
 * Provides a webform deriver.
 */
class GroupWebformDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    assert($base_plugin_definition instanceof GroupRelationTypeInterface);

    $this->derivatives['webform'] = clone $base_plugin_definition;
    $this->derivatives['webform']->set('label', $this->t('Group webform'));
    $this->derivatives['webform']->set('description', $this->t('Adds webforms to groups'));

    return $this->derivatives;
  }

}
