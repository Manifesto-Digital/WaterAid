<?php

namespace Drupal\wateraid_base_core\Plugin\DsFieldTemplate;

use Drupal\ds\Plugin\DsFieldTemplate\DsFieldTemplateBase;

/**
 * Plugin for the unordered_list field template.
 *
 * @DsFieldTemplate(
 *   id = "wa_list",
 *   title = @Translation("List"),
 *   theme = "wateraid_ds_field_wa_list",
 * )
 */
class WaterAidList extends DsFieldTemplateBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = parent::defaultConfiguration();
    $config['lb'] = '';
    $config['label_element'] = 'div';
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

    $form['label_element'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label element'),
      '#size' => '10',
      '#default_value' => $config['label_element'],
    ];
    return $form;
  }

}
