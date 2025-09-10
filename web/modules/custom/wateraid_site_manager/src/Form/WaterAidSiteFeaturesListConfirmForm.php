<?php

namespace Drupal\wateraid_site_manager\Form;

use Drupal\Core\Url;
use Drupal\system\Form\ModulesListConfirmForm;

/**
 * WaterAid Site Features List Confirm Form.
 *
 * @package Drupal\wateraid_site_manager\Form
 */
class WaterAidSiteFeaturesListConfirmForm extends ModulesListConfirmForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wateraid_features_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('wateraid.admin_site_features');
  }

}
