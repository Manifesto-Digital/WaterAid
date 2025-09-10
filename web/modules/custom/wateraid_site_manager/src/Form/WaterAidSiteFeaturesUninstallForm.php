<?php

namespace Drupal\wateraid_site_manager\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Update\UpdateHookRegistry;
use Drupal\system\Form\ModulesUninstallForm;

/**
 * Provides a form for uninstalling modules.
 */
class WaterAidSiteFeaturesUninstallForm extends ModulesUninstallForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wateraid_features_uninstall';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Make sure the install API is available.
    include_once DRUPAL_ROOT . '/core/includes/install.inc';

    // Get a list of all available modules.
    $modules = \Drupal::service('extension.list.module')->getList();
    $uninstallable = array_filter($modules, static function ($module) use ($modules) {
      return empty($modules[$module->getName()]->info['required']) && $module->status;
    });

    // Include system.admin.inc so we can use the sort callbacks.
    $this->moduleHandler->loadInclude('system', 'inc', 'system.admin');

    $form['modules'] = [];

    // Only build the rest of the form if there are any modules available to
    // uninstall.
    if (empty($uninstallable)) {
      return $form;
    }

    $profile = \Drupal::installProfile();

    // Sort all modules by their name.
    uasort($uninstallable, 'system_sort_modules_by_info_name');
    $validation_reasons = $this->moduleInstaller->validateUninstall(array_keys($uninstallable));

    $form['uninstall'] = ['#tree' => TRUE];

    foreach ($uninstallable as $module_key => $module) {
      // Use wateraid.package info to filter modules.
      if (!empty($module->info['package']) && $module->info['package'] === 'wateraid' && !empty($module->info['wateraid.package'])) {
        $name = $module->info['name'] ?: $module->getName();
        $form['modules'][$module->getName()]['#module_name'] = $name;
        $form['modules'][$module->getName()]['name']['#markup'] = $name;
        $form['modules'][$module->getName()]['description']['#markup'] = $module->info['description'];

        $form['uninstall'][$module->getName()] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Uninstall @module module', ['@module' => $name]),
          '#title_display' => 'invisible',
        ];

        // If a validator returns reasons not to uninstall a module,
        // list the reasons and disable the checkbox.
        if (isset($validation_reasons[$module_key])) {
          $form['modules'][$module->getName()]['#validation_reasons'] = $validation_reasons[$module_key];
          $form['uninstall'][$module->getName()]['#disabled'] = TRUE;
        }

        // All modules which depend on this one must be uninstalled first,
        // before we can allow this module to be uninstalled. (The installation
        // profile is excluded from this list.)
        foreach (array_keys($module->required_by) as $dependent) {
          if ($dependent !== $profile && $dependent !== UpdateHookRegistry::SCHEMA_UNINSTALLED) {
            $name = $modules[$dependent]->info['name'] ?? $dependent;
            $form['modules'][$module->getName()]['#required_by'][] = $name;
            $form['uninstall'][$module->getName()]['#disabled'] = TRUE;
          }
        }
      }
    }

    $form['#attached']['library'][] = 'system/drupal.system.modules';
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Uninstall'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Form submitted, but no modules selected.
    if (!array_filter($form_state->getValue('uninstall'))) {
      $form_state->setErrorByName('', $this->t('No modules selected.'));
      $form_state->setRedirect('wateraid.admin_site_features_uninstall');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    // Redirect to the confirm form.
    $form_state->setRedirect('wateraid.admin_site_features_uninstall_confirm');
  }

}
