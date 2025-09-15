<?php

namespace Drupal\group_webform\Plugin\Group\Relation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Plugin\Attribute\GroupRelationType;
use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a relation enabler for webforms.
 */
#[GroupRelationType(
  id: 'group_webform',
  entity_type_id: 'webform',
  label: new TranslatableMarkup('Group webform'),
  description: new TranslatableMarkup('Adds webforms to groups both publicly and privately.'),
  reference_label: new TranslatableMarkup('Title'),
  reference_description: new TranslatableMarkup('The title of the webform to add to the group'),
  entity_access: TRUE,
  deriver: 'Drupal\group_webform\Plugin\Group\Relation\GroupWebformDeriver'
)]
class GroupWebform extends GroupRelationBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
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
