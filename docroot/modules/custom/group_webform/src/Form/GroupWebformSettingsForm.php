<?php

namespace Drupal\group_webform\Form;

use Drupal\Core\Datetime\DrupalDateTime;
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

    $result = 'placeholder';
    $start = 0;

    $future = strtotime('now +3 months');

    while (!empty($result)) {
      $query = \Drupal::database()->select('media__field_dam_expiry_date', 'e')
        ->condition('field_dam_expiry_date_value', $future, '<');
      $query->leftJoin('entity_usage', 'u', 'u.target_id = e.entity_id AND u.target_type = :type', [
        ':type' => 'media',
      ]);
      $query->leftJoin('entity_usage', 'pu', 'pu.target_id = u.source_id AND pu.target_type = u.source_type');
      $query->fields('e', ['entity_id', 'field_dam_expiry_date_value']);
      $query->fields('u', ['source_id', 'source_type']);
      $query->range($start, 100);
      $result = $query->execute()->fetchAll();

      foreach ($result as $item) {
        \Drupal::queue('wa_orange_dam_usage_processor')->createItem($item);
      }

      $start += 100;
    }

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
