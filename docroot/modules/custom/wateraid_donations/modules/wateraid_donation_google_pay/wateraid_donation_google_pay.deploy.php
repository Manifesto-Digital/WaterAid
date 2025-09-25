<?php

/**
 * @file
 * Provides deploy hooks for the wateraid_donation_google_pay module.
 */

/**
 * Install Google Pay by default on one-off donation webforms.
 */
function wateraid_donation_google_pay_deploy_0001_install_googlepay(&$sandbox) {
  /** @var \Drupal\webform\WebformEntityStorage $webform_storage */
  $webform_storage = \Drupal::entityTypeManager()->getStorage('webform');
  $webforms = $webform_storage->loadMultiple();

  /** @var \Drupal\webform\WebformInterface $webform */
  foreach ($webforms as $webform) {
    /** @var \Drupal\webform\Plugin\WebformHandlerInterface $handler */
    foreach ($webform->getHandlers() as $handler) {
      if ($handler->getPluginId() == 'wateraid_donations') {
        $settings = $handler->getSettings();

        if ($settings['one_off']['enabled'] === 1 && !in_array('googlepay', $settings['one_off']['payment_methods'])) {
          $settings['one_off']['payment_methods'][] = 'googlepay';
          $handler->setSettings($settings);
          $webform_id = $webform->id();
          $webform->updateWebformHandler($handler);
          try {
            $webform->save();
          }
          catch (\Exception $e) {
            \Drupal::messenger()->addError($e->getMessage());
          }
          \Drupal::messenger()->addMessage(t("Enabled Google Pay on webform: @webform_id", ['@webform_id' => $webform_id]));
        }
        break;
      }
    }
  }
}
