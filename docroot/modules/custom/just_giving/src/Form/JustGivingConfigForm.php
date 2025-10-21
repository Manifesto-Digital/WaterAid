<?php

namespace Drupal\just_giving\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\just_giving\JustGivingSearch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for Just Giving config.
 */
class JustGivingConfigForm extends ConfigFormBase {

  /**
   * Drupal\just_giving\JustGivingClient definition.
   */
  protected JustGivingSearch $justGivingSearch;

  /**
   * JustGivingConfigForm constructor.
   *
   * @param \Drupal\just_giving\JustGivingSearch $jg_search
   *   Just giving search.
   */
  public function __construct(JustGivingSearch $jg_search) {
    $this->justGivingSearch = $jg_search;
  }

  /**
   * Creates new just giving config form object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('just_giving.search')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'just_giving.justgivingconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'just_giving_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('just_giving.justgivingconfig');
    $form['environments'] = [
      '#type' => 'select',
      '#title' => $this->t('Environments'),
      '#description' => $this->t('Choose between sandbox and production environment endpoints'),
      '#options' => [
        'https://api.staging.justgiving.com/' => $this->t('https://api.staging.justgiving.com/'),
        'https://api.justgiving.com/' => $this->t('https://api.justgiving.com/'),
      ],
      '#size' => 1,
      '#default_value' => $config->get('environments'),
    ];
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App ID'),
      '#description' => $this->t('Just Giving App ID: https://developer.justgiving.com/admin/applications'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];
    $form['api_version'] = [
      '#type' => 'select',
      '#title' => $this->t('API Version'),
      '#description' => $this->t('Choose API version (currently only version 1 available)'),
      '#options' => ['1' => $this->t('1')],
      '#size' => 1,
      '#default_value' => $config->get('api_version'),
    ];
    $form['charity_id'] = [
      '#type' => 'textfield',
      '#size' => 20,
      '#title' => $this->t('Charity Id'),
      '#autocomplete_route_name' => 'just_giving.search_autocomplete',
      '#autocomplete_route_parameters' => [
        'search_type' => 'charity',
        'field_name' => 'charity_id',
        'count' => 10,
      ],
      '#description' => $this->t(
        'Enter Charity ID or the charity name to choose the Charity ID that campaigns will be associated with,
      found here: https://www.justgiving.com/charities/Settings/charity-profile'
      ),
      '#default_value' => $config->get('charity_id'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $this->config('just_giving.justgivingconfig')
      ->set('environments', $form_state->getValue('environments'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('api_version', $form_state->getValue('api_version'))
      ->set('charity_id', $form_state->getValue('charity_id'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->save();
  }

}
