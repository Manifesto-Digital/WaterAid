<?php

namespace Drupal\wateraid_site_manager\Form;

use Drupal\Core\Url;
use Drupal\system\Form\ModulesUninstallConfirmForm;

/**
 * Builds a confirmation form to uninstall selected modules.
 */
class WaterAidSiteFeaturesUninstallConfirmForm extends ModulesUninstallConfirmForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('wateraid.admin_site_features_uninstall');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wateraid_features_uninstall_confirm';
  }

}
