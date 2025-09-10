<?php

namespace Drupal\wateraid_site_manager\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\system\Form\ModulesListForm;

/**
 * Provides module installation interface.
 *
 * The list of modules gets populated by module.info.yml files, which contain
 * each module's name, description, and information about which modules it
 * requires. See \Drupal\Core\Extension\InfoParser for info on module.info.yml
 * descriptors.
 */
class WaterAidSiteFeaturesListForm extends ModulesListForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wateraid_features';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $wateraid_modules = [];
    foreach (\Drupal::service('extension.list.module')->getList() as $module_name => $module) {
      if (empty($module->info['hidden'])) {
        $package = $module->info['package'];

        // Use wateraid.package info to group and filter modules.
        if ($package === 'wateraid' && !empty($module->info['wateraid.package'])) {
          $wateraid_modules[] = $module_name;
        }
      }
    }

    foreach (Element::children($form['modules']) as $package) {
      if ($package === 'wateraid') {
        foreach (Element::children($form['modules'][$package]) as $module) {
          if (!in_array($module, $wateraid_modules)) {
            unset($form['modules'][$package][$module]);
          }
        }
      }
      else {
        unset($form['modules'][$package]);
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\Core\Url $url */
    if ($url = $form_state->getRedirect()) {
      $route_name = $url->getRouteName();
      if ($route_name === 'system.modules_list_confirm' || $route_name === 'system.modules_list_experimental_confirm') {
        $form_state->setRedirect('wateraid.admin_site_features_confirm');
      }
    }
  }

}
