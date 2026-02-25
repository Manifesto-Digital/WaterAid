<?php

namespace Drupal\wateraid_tmgmt\Form;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt_smartling\Smartling\ConfigManager\SmartlingConfigManager;
use Drupal\tmgmt_smartling\Smartling\SmartlingApiWrapper;
use Smartling\TranslationRequests\Params\SearchTranslationRequestParams;
use Smartling\TranslationRequests\Params\TranslationSubmissionStates;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Remote Error Log form.
 */
class RemoteErrorLogForm extends FormBase {

  /**
   * Drupal\Component\Transliteration\TransliterationInterface definition.
   */
  protected TransliterationInterface $transliteration;

  /**
   * Drupal\Core\State\StateInterface definition.
   */
  protected StateInterface $state;

  /**
   * Drupal\tmgmt_smartling\Smartling\SmartlingApiWrapper definition.
   */
  protected SmartlingApiWrapper $wrapper;

  /**
   * Provider configs.
   */
  protected array $smartlingProviderConfigs;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->state = $container->get('state');
    $instance->wrapper = $container->get('tmgmt_smartling.smartling_api_wrapper');
    $instance->smartlingProviderConfigs = $container
      ->get("tmgmt_smartling.smartling_config_manager")
      ->getAvailableConfigs();
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'remote_error_log_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Number of results to show.
    $limit = 100;

    // Result offset for pagination.
    $offset = 0;

    $form['info'] = [
      '#markup' => $this->t('<p>This report performs a live call to the Smartling API showing the top @limit
        jobs where the translation state is "Failed".</p>
        <p>When cron runs, only jobs in the "Translated" state are returned. "Failed" jobs are stuck indefinitely.</p>',
        [
          '@limit' => $limit,
        ]),
    ];

    $rows = [];
    $smartling_provider_configs = $this->smartlingProviderConfigs;

    foreach ($smartling_provider_configs as $smartling_provider_config) {
      $api_wrapper = $this->wrapper;
      $settings = $smartling_provider_config->get("settings");
      $api_wrapper->setSettings($settings);

      $search_params = new SearchTranslationRequestParams();
      $search_params->setState(TranslationSubmissionStates::STATE_FAILED);
      $search_params->setLimit($limit);
      $search_params->setOffset($offset);

      $bucket_name = $this->state->get('tmgmt_smartling.bucket_name', 'tmgmt_smartling_default_bucket_name');
      $result = $api_wrapper->searchTranslationRequest($bucket_name, $search_params);

      foreach ($result as $failed_job) {
        $id = $failed_job['originalAssetKey']['tmgmt_job_id'];
        $job = Job::load($id);

        if ($job) {
          $job_link = $job->toLink($id);
        }
        else {
          $job_link = $id;
        }

        $rows[] = [
          'id' => $job_link,
          'title' => $failed_job['title'],
          'target_locale' => $failed_job['translationSubmissions'][0]['targetLocaleId'],
          'state' => $failed_job['translationSubmissions'][0]['state'],
          'percent_complete' => $failed_job['translationSubmissions'][0]['percentComplete'],
          'created' => $failed_job['translationSubmissions'][0]['createdDate'],
          'modified' => $failed_job['translationSubmissions'][0]['modifiedDate'],
          'last_error_message' => $failed_job['translationSubmissions'][0]['lastErrorMessage'],
          'request_uid' => $failed_job['translationRequestUid'],
          'submission_uid' => $failed_job['translationSubmissions'][0]['translationSubmissionUid'],
        ];
      }
    }

    $header = [
      'id' => $this->t('TMGMT Job ID'),
      'title' => $this->t('Title'),
      'target_locale' => $this->t('Target locale'),
      'state' => $this->t('State'),
      'percent_complete' => $this->t('Percent complete'),
      'created' => $this->t('Created'),
      'modified' => $this->t('Modified'),
      'last_error_message' => $this->t('Last error message'),
      'request_uid' => $this->t('Request UID'),
      'submission_uid' => $this->t('Submission UID'),
    ];

    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $rows,
      '#empty' => $this->t('No items found'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // @todo Process items.
  }

}
