<?php

namespace Drupal\group_webform\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group_webform\GroupWebformService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The group webform settings form.
 */
class GroupWebformSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'groupwebform_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['group_webform.settings'];
  }

  /**
   * The group webform service.
   *
   * @var \Drupal\group_webform\GroupWebformService
   */
  protected GroupWebformService $groupWebformService;

  /**
   * {@inheritdoc}
   */
  public function __construct(GroupWebformService $groupWebformService) {
    $this->groupWebformService = $groupWebformService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('groupwebform.webform')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->groupWebformService->getConfig();

    $form['show_all_webforms_in_gwselect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show all webforms in Group Webform Select field'),
      '#description' => $this->t("By default the Group Webform Select Field only shows webforms with Group Relationships. Check to show all user-accessible webforms in the Select Field. Additonal webforms will only appear for users with 'Administer webform' permission."),
      '#default_value' => $config->get('show_all_webforms_in_gwselect'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $show_all_webforms = $form_state->getValue('show_all_webforms_in_gwselect');
    $this->groupWebformService->setConfig('show_all_webforms_in_gwselect', $show_all_webforms);

    parent::submitForm($form, $form_state);
  }

}
