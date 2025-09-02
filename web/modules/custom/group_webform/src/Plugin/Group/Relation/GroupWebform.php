<?php

namespace Drupal\group_webform\Plugin\Group\Relation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a relation enabler for webforms.
 *
 * @GroupRelationType(
 *   id = "group_webform",
 *   label = @Translation("Group webform"),
 *   description = @Translation("Adds webforms to groups both publicly and privately."),
 *   entity_type_id = "webform",
 *   entity_access = TRUE,
 *   pretty_path_key = "webform",
 *   reference_label = @Translation("Title"),
 *   reference_description = @Translation("The title of the webform to add to the group"),
 *   deriver = "Drupal\group_webform\Plugin\Group\Relation\GroupWebformDeriver",
 * )
 */
class GroupWebform extends GroupRelationBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    if (isset($form['use_creation_wizard'])) {
      $form['use_creation_wizard']['#access'] = FALSE;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    // Only attach a webform config as a dependency if present.
    $bundle = $this->getRelationType()->getEntityBundle();
    if (isset($bundle) && strlen($bundle)) {
      $dependencies['config'][] = 'system.webform.' . $bundle;
    }

    return $dependencies;
  }

}
