<?php

namespace Drupal\wateraid_base_core\Plugin\DsFieldTemplate;

use Drupal\ds\Plugin\DsFieldTemplate\DsFieldTemplateBase;

/**
 * Plugin for the implode field template.
 *
 * @DsFieldTemplate(
 *   id = "wa_implode",
 *   title = @Translation("Implode"),
 *   theme = "wateraid_ds_field_wa_implode",
 * )
 */
class Implode extends DsFieldTemplateBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = parent::defaultConfiguration();
    $config['glue'] = '';
    $config['lb'] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(&$form) {
    $config = $this->getConfiguration();
    parent::alterForm($form);

    // Add label.
    $form['lb'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#size' => '10',
      '#default_value' => $config['lb'],
    ];

    $form['glue'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Glue'),
      '#size' => '10',
      '#default_value' => $config['glue'],
    ];

    return $form;
  }

}
