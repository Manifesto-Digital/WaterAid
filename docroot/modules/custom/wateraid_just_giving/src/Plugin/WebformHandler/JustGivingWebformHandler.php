<?php

namespace Drupal\wateraid_just_giving\Plugin\WebformHandler;

use Drupal\Component\Utility\EmailValidator;
use Drupal\Core\Form\FormStateInterface;
use Drupal\just_giving\JustGivingAccountInterface;
use Drupal\just_giving\JustGivingClientInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Just Giving Webform Handler.
 *
 * @package Drupal\wateraid_just_giving\Plugin\WebformHandler
 *
 * @WebformHandler(
 *   id = "wateraid_just_giving",
 *   label = @Translation("WaterAid JustGiving"),
 *   category = @Translation("WaterAid JustGiving"),
 *   description = @Translation("Processes JustGiving registrations."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class JustGivingWebformHandler extends WebformHandlerBase {

  private const FUNDRAISER_PAGE_SHORT_NAME = 'fundraiser_page_short_name';

  private const CAMPAIGN_SHORT_NAME = 'campaign_short_name';

  /**
   * The email validator.
   */
  private EmailValidator $emailValidator;

  /**
   * Just Giving client service.
   */
  private JustGivingClientInterface $justGivingClient;

  /**
   * Just Giving account service.
   */
  private JustGivingAccountInterface $justGivingAccount;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setJustGivingClient($container->get('just_giving.client'));
    $instance->setJustGivingAccount($container->get('just_giving.account'));
    return $instance;
  }

  /**
   * Sets the Just Giving Client.
   *
   * @param \Drupal\just_giving\JustGivingClientInterface $just_giving_client
   *   Just Giving Client service class.
   *
   * @return $this
   */
  protected function setJustGivingClient(JustGivingClientInterface $just_giving_client): static {
    $this->justGivingClient = $just_giving_client;
    return $this;
  }

  /**
   * Sets the Just Giving Account.
   *
   * @param \Drupal\just_giving\JustGivingAccountInterface $just_giving_account
   *   Just Giving Account service class.
   *
   * @return $this
   */
  protected function setJustGivingAccount(JustGivingAccountInterface $just_giving_account): static {
    $this->justGivingAccount = $just_giving_account;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $textFieldOptions = [];
    $emailOptions = [];
    $passwordOptions = [];
    $checkboxOptions = [];
    $selectOptions = [];
    $addressOptions = [];
    $hiddenOptions = [];

    $elements = $this->getWebform()->getElementsDecodedAndFlattened();

    // Pre-process mapping of field types.
    foreach ($elements as $key => $element) {
      switch ($element['#type']) {
        case 'textfield':
          $textFieldOptions[$key] = $element['#title'];
          break;

        case 'email':
        case 'webform_email_confirm':
          $emailOptions[$key] = $element['#title'];
          break;

        case 'password':
        case 'password_confirm':
          $passwordOptions[$key] = $element['#title'];
          break;

        case 'checkbox':
          $checkboxOptions[$key] = $element['#title'];
          break;

        case 'select':
          $selectOptions[$key] = $element['#title'];
          break;

        case 'webform_address':
          $addressOptions[$key] = $element['#title'];
          break;

        case 'hidden':
          // Hidden elements do not have a title. Use key instead.
          $hiddenOptions[$key] = $key;
          break;
      }
    }

    $form['auth_fields'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('JustGiving authentication fields'),
      '#description' => $this->t('Set the fields that will be used to authenticate a user in JustGiving.'),
    ];

    $form['auth_fields']['email'] = [
      '#type' => 'select',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
      '#options' => $emailOptions,
      '#default_value' => $this->configuration['auth_fields']['email'],
    ];

    $form['auth_fields']['password'] = [
      '#type' => 'select',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
      '#options' => $passwordOptions,
      '#default_value' => $this->configuration['auth_fields']['password'],
    ];

    $form['account_fields'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('JustGiving account fields'),
      '#description' => $this->t('Set the fields that will be used to register a user account in JustGiving.'),
    ];

    $form['account_fields']['create_account_consent'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Create Account Consent'),
    ];

    $form['account_fields']['create_account_consent']['element'] = [
      '#type' => 'select',
      '#title' => $this->t('Element'),
      '#required' => TRUE,
      '#options' => $checkboxOptions + $selectOptions,
      '#default_value' => $this->configuration['account_fields']['create_account_consent']['element'],
    ];

    $form['account_fields']['create_account_consent']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#default_value' => $this->configuration['account_fields']['create_account_consent']['value'],
    ];

    $form['account_fields']['first_name'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $textFieldOptions,
      '#title' => $this->t('First Name'),
      '#default_value' => $this->configuration['account_fields']['first_name'],
    ];

    $form['account_fields']['last_name'] = [
      '#type' => 'select',
      '#required' => FALSE,
      '#options' => $textFieldOptions,
      '#title' => $this->t('Last Name (Optional)'),
      '#default_value' => $this->configuration['account_fields']['last_name'],
    ];

    $form['account_fields']['address'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $addressOptions,
      '#title' => $this->t('Address'),
      '#default_value' => $this->configuration['account_fields']['address'],
    ];

    // acceptTermsAndConditions.
    $form['account_fields']['terms'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $checkboxOptions,
      '#title' => $this->t('JustGiving Terms and Conditions'),
      '#default_value' => $this->configuration['account_fields']['terms'],
    ];

    $form['fundraiser_page_fields'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('JustGiving fundraiser page fields'),
      '#description' => $this->t('Set the fields that will be used to register a fundraiser page in JustGiving.'),
    ];

    $form['fundraiser_page_fields']['create_fundraiser_page_consent'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Create Fundraiser Page Consent'),
    ];

    $form['fundraiser_page_fields']['create_fundraiser_page_consent']['element'] = [
      '#type' => 'select',
      '#title' => $this->t('Element'),
      '#required' => TRUE,
      '#options' => $checkboxOptions + $selectOptions,
      '#default_value' => $this->configuration['fundraiser_page_fields']['create_fundraiser_page_consent']['element'],
    ];

    $form['fundraiser_page_fields']['create_fundraiser_page_consent']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#default_value' => $this->configuration['fundraiser_page_fields']['create_fundraiser_page_consent']['value'],
    ];

    $form['fundraiser_page_fields']['charity_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Charity Id'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['fundraiser_page_fields']['charity_id'],
      '#autocomplete_route_name' => 'just_giving.search_autocomplete',
      '#autocomplete_route_parameters' => [
        'search_type' => 'charity',
        'field_name' => 'charity_id',
        'count' => 10,
      ],
      '#description' => $this->t('Enter Charity ID or the charity name to choose the Charity ID that fundraiser pages will be associated with, found here: https://www.justgiving.com/charities/Settings/charity-profile'),
    ];

    $form['fundraiser_page_fields']['event_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Id'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['fundraiser_page_fields']['event_id'],
      '#autocomplete_route_name' => 'just_giving.search_autocomplete',
      '#autocomplete_route_parameters' => [
        'search_type' => 'event',
        'field_name' => 'event_id',
        'count' => 10,
      ],
      '#description' => $this->t('The eventId argument specifies the event to create the fundraising page for.'),
    ];

    $form['fundraiser_page_fields'][self::CAMPAIGN_SHORT_NAME] = [
      '#type' => 'textfield',
      '#title' => $this->t('Campaign Name (Optional)'),
      '#required' => FALSE,
      '#default_value' => $this->configuration['fundraiser_page_fields'][self::CAMPAIGN_SHORT_NAME],
      '#description' => $this->t('The campaign short name (as displayed in the URL for the campaign).'),
    ];

    $form['fundraiser_page_fields']['title'] = [
      '#type' => 'select',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
      '#options' => $textFieldOptions,
      '#maxlength' => 75,
      '#default_value' => $this->configuration['fundraiser_page_fields']['title'],
    ];

    $form['fundraiser_page_fields']['url'] = [
      '#type' => 'select',
      '#title' => $this->t('URL'),
      '#required' => TRUE,
      '#options' => $hiddenOptions,
      '#default_value' => $this->configuration['fundraiser_page_fields']['url'],
      '#description' => $this->t('Requires a hidden webform element. Used to persist back the Fundraiser Page URL into the webform submission.'),
    ];

    $form['fundraiser_page_fields']['id'] = [
      '#type' => 'select',
      '#title' => $this->t('Id'),
      '#required' => TRUE,
      '#options' => $hiddenOptions,
      '#default_value' => $this->configuration['fundraiser_page_fields']['id'],
      '#description' => $this->t('Requires a hidden webform element. Used to persist back the Fundraiser Page Id into the webform submission.'),
    ];

    // justGivingOptIn.
    $form['fundraiser_page_fields']['preferences'] = [
      '#type' => 'select',
      '#title' => $this->t('JustGiving Preferences'),
      '#required' => TRUE,
      '#options' => $checkboxOptions,
      '#default_value' => $this->configuration['fundraiser_page_fields']['preferences'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);

    $fundraiserPageFields = $form_state->getValue('fundraiser_page_fields');

    // Check if provided.
    if (!empty($fundraiserPageFields[self::CAMPAIGN_SHORT_NAME])) {
      // Get campaign details.
      $campaignDetailsResponse = $this->campaignDetails($fundraiserPageFields[self::CAMPAIGN_SHORT_NAME]);
      // Expecting 201 response code on success.
      if ($campaignDetailsResponse && $campaignDetailsResponse->httpStatusCode !== 200) {
        // Log error but continue page create execution.
        $this->getLogger()->error(json_encode($campaignDetailsResponse));
        $form_state->setErrorByName($this->configuration['fundraiser_page_fields'][self::CAMPAIGN_SHORT_NAME], $this->t('Something went wrong whilst validating the Campaign Name.'));
      }
      else {
        // Validate campaign against charity.
        if ($campaignDetailsResponse->bodyResponse->charities[0]->id !== (int) $fundraiserPageFields['charity_id']) {
          $form_state->setErrorByName($this->configuration['fundraiser_page_fields'][self::CAMPAIGN_SHORT_NAME], $this->t('The Campaign does not belong to specified Charity.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['auth_fields'] = $form_state->getValue('auth_fields');
    $this->configuration['account_fields'] = $form_state->getValue('account_fields');
    $this->configuration['fundraiser_page_fields'] = $form_state->getValue('fundraiser_page_fields');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission): void {
    parent::validateForm($form, $form_state, $webform_submission);

    try {
      // Check fundraiser page creation consent.
      if ($this->createFundraiserPageConsent($form_state)) {
        // Check account creation consent.
        if ($this->createAccountConsent($form_state)) {
          // Valid email address check.
          if ($this->emailValidator->isValid($form_state->getValue($this->configuration['auth_fields']['email'])) !== TRUE) {
            $form_state->setErrorByName($this->configuration['auth_fields']['email'], $this->t('This email address is not valid.'));
          }
          // Validate existing account.
          if ($this->justGivingAccount->checkAccountExists($form_state->getValue($this->configuration['auth_fields']['email'])) === TRUE) {
            $form_state->setErrorByName($this->configuration['auth_fields']['email'], $this->t('This email address is already in use.'));
          }
          // JustGiving adds a password length constraint.
          if (mb_strlen($form_state->getValue($this->configuration['auth_fields']['password'])) < 8) {
            $form_state->setErrorByName($this->configuration['auth_fields']['password'], $this->t('Your password should be at least 8 characters long.'));
          }
          // Validate mandatory acceptTermsAndConditions.
          if ((bool) $form_state->getValue($this->configuration['account_fields']['terms']) !== TRUE) {
            $form_state->setErrorByName($this->configuration['account_fields']['terms'], $this->t('You must agree to the terms of service.'));
          }
        }
        else {
          // If no account creation consent is given, then we are to be passed
          // existing JustGiving account credentials, which we need to validate.
          $validateAccountResponse = $this->justGivingAccount->validateAccount(
            $form_state->getValue($this->configuration['auth_fields']['email']),
            $form_state->getValue($this->configuration['auth_fields']['password'])
          );
          // Check response.
          if ($validateAccountResponse->isValid !== TRUE) {
            $form_state->setErrorByName($this->configuration['auth_fields']['email'], $this->t('Invalid JustGiving credentials.'));
            $form_state->setErrorByName($this->configuration['auth_fields']['password']);
          }
        }
        // Validate Fundraiser Page Title to be passed.
        if (empty($form_state->getValue($this->configuration['fundraiser_page_fields']['title']))) {
          $form_state->setErrorByName($this->configuration['fundraiser_page_fields']['title'], $this->t('Fundraiser page title is required.'));
        }
        if (!preg_match('/^[a-zA-Z0-9]*[a-zA-z0-9 \.\*\\\?\(\)\:\,\;\-]/', $form_state->getValue($this->configuration['fundraiser_page_fields']['title']))) {
          $form_state->setErrorByName($this->configuration['fundraiser_page_fields']['title'], $this->t('Fundraiser page title can contain only letters, numbers and common punctuation. It must not start with a hyphen.'));
        }
        else {
          $fundraiserPageShortName = FALSE;
          do {
            $suggestions = $this->fundraisingPageUrlSuggestions($form_state->getValue($this->configuration['fundraiser_page_fields']['title']));
            // Iterate suggestions.
            foreach ($suggestions->Names as $suggestion) {
              // Validate existing page short name.
              if ($this->fundraisingPageExists($suggestion) !== TRUE) {
                // Set short name value in $form_state.
                $form_state->setValue(self::FUNDRAISER_PAGE_SHORT_NAME, $suggestion);
                $fundraiserPageShortName = TRUE;
                break;
              }
            }
          } while ($fundraiserPageShortName === FALSE);
        }
      }
    }
    catch (\Exception $e) {
      // Something went wrong.
      $this->getLogger()->error($e->getMessage());
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission): void {

    $data = $webform_submission->getData();

    try {
      // Check fundraiser page creation consent.
      if ($this->createFundraiserPageConsent($form_state)) {
        // Check account creation consent.
        if ($this->createAccountConsent($form_state)) {
          $signupUserResponse = $this->signupUser($form_state);
          // Check response code, expected 200.
          if ($signupUserResponse->httpStatusCode !== 200) {
            // If account creation failed, then do not proceed with
            // fundraiser page creation.
            $this->getLogger()->error(json_encode($signupUserResponse));
            \Drupal::messenger()->addMessage($this->t('Your registration has been successful, but unfortunately we were not able to create your JustGiving account and fundraiser page. Please go to https://www.justgiving.com/ to complete set up.'), 'warning');
            $this->postSubmitCleanUp($data, $webform_submission);
            return;
          }
        }
        // The $this->justGivingPage->registerFundraisingPage() is very hard
        // coupled, for the time being we will have to build the API call
        // ourselves.
        $registerFundraiserPageResponse = $this->registerFundraisingPage($form_state);
        // Expecting 201 response code on success.
        if ($registerFundraiserPageResponse->httpStatusCode !== 201) {
          // We appear to receive a lot of 503, 504, ... response codes...
          $this->getLogger()->error(json_encode($registerFundraiserPageResponse));
          \Drupal::messenger()->addMessage($this->t('Your registration has been successful, but unfortunately we were not able to create your JustGiving fundraiser page. Please go to https://www.justgiving.com/ to complete set up.'), 'warning');
          $this->postSubmitCleanUp($data, $webform_submission);
          return;
        }
        // Success response, so retrieve Page ID from payload.
        $data[$this->configuration['fundraiser_page_fields']['id']] = $registerFundraiserPageResponse->bodyResponse->pageId;

        // Check environment from settings.
        $config = $this->configFactory->get('just_giving.justgivingconfig');
        $justGivingBaseUrl = 'https://www.justgiving.com/fundraising/';
        if (str_contains($config->get('environments'), 'staging')) {
          $justGivingBaseUrl = 'https://www.staging.justgiving.com/fundraising/';
        }

        // Save Page URL.
        $data[$this->configuration['fundraiser_page_fields']['url']] = $justGivingBaseUrl . $form_state->getValue(self::FUNDRAISER_PAGE_SHORT_NAME);
      }
    }
    catch (\Exception $e) {
      // Something else went wrong.
      $this->getLogger()->error($e->getMessage());
      \Drupal::messenger()->addError($this->t('Something went wrong: @msg', ['@msg' => $e->getMessage()]));
    }

    $this->postSubmitCleanUp($data, $webform_submission);
  }

  /**
   * Post clean up helper.
   *
   * @param mixed[] $data
   *   Webform data set.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission.
   */
  private function postSubmitCleanUp(array $data, WebformSubmissionInterface $webform_submission): void {
    // Remove password field value after JustGiving API calls.
    unset($data[$this->configuration['auth_fields']['password']]);
    // Persist back data set.
    // Deliberately not calling save() on $webform_submission.
    $webform_submission->setData($data);
  }

  /**
   * Checks if consent is given for account creation.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  private function createAccountConsent(FormStateInterface $form_state): bool {
    return $form_state->getValue($this->configuration['account_fields']['create_account_consent']['element']) === $this->configuration['account_fields']['create_account_consent']['value'];
  }

  /**
   * Checks if consent is given for fundraiser page creation.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  private function createFundraiserPageConsent(FormStateInterface $form_state): bool {
    return $form_state->getValue($this->configuration['fundraiser_page_fields']['create_fundraiser_page_consent']['element']) === $this->configuration['fundraiser_page_fields']['create_fundraiser_page_consent']['value'];
  }

  /**
   * Sign up a user.
   *
   * Modified request builder.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface.
   *
   * @return mixed
   *   the response from the sign-up API.
   */
  private function signupUser(FormStateInterface $form_state): mixed {

    $addressValues = $form_state->getValue($this->configuration['account_fields']['address']);

    $address = new \Address();
    $address->line1 = $addressValues['address'];
    $address->line2 = $addressValues['address_2'];
    $address->townOrCity = $addressValues['city'];
    $address->countyOrState = $addressValues['state_province'];
    $address->country = $addressValues['country'];
    $address->postcodeOrZipcode = $addressValues['postal_code'];

    $accRequest = new \CreateAccountRequest();
    $accRequest->reference = NULL;
    $accRequest->title = FALSE;
    $accRequest->firstName = $form_state->getValue($this->configuration['account_fields']['first_name']);
    $accRequest->lastName = $form_state->getValue($this->configuration['account_fields']['last_name']) ?? NULL;
    $accRequest->email = $form_state->getValue($this->configuration['auth_fields']['email']);
    $accRequest->password = $form_state->getValue($this->configuration['auth_fields']['password']);
    $accRequest->acceptTermsAndConditions = (bool) $form_state->getValue($this->configuration['account_fields']['terms']);
    $accRequest->address = $address;

    return $this->justGivingClient->jgLoad()->Account->CreateV2($accRequest);
  }

  /**
   * Register fundraiser page.
   *
   * This method is inherited from the JustGivingPage service and adapted to be
   * more loosely coupled.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface.
   *
   * @return mixed
   *   The response from the register API.
   */
  private function registerFundraisingPage(FormStateInterface $form_state): mixed {

    $regPageRequest = new \RegisterPageRequest();
    $regPageRequest->reference = NULL;
    $regPageRequest->charityId = $this->configuration['fundraiser_page_fields']['charity_id'];
    $regPageRequest->eventId = $this->configuration['fundraiser_page_fields']['event_id'];
    $regPageRequest->causeId = NULL;
    $regPageRequest->pageTitle = $form_state->getValue($this->configuration['fundraiser_page_fields']['title']);
    $regPageRequest->pageShortName = $form_state->getValue(self::FUNDRAISER_PAGE_SHORT_NAME);
    $regPageRequest->charityOptIn = (bool) $form_state->getValue($this->configuration['fundraiser_page_fields']['preferences']);
    $regPageRequest->charityFunded = FALSE;

    // This endpoint requires user authentication.
    $this->justGivingClient->setUsername($form_state->getValue($this->configuration['auth_fields']['email']));
    $this->justGivingClient->setPassword($form_state->getValue($this->configuration['auth_fields']['password']));

    // Check if this is in context of a campaign.
    if (!empty($this->configuration['fundraiser_page_fields'][self::CAMPAIGN_SHORT_NAME])) {
      // Get campaign details.
      $campaignDetailsResponse = $this->campaignDetails($this->configuration['fundraiser_page_fields'][self::CAMPAIGN_SHORT_NAME]);
      // Expecting 201 response code on success.
      if ($campaignDetailsResponse && $campaignDetailsResponse->httpStatusCode !== 200) {
        // Log error but continue page create execution.
        $this->getLogger()->error(json_encode($campaignDetailsResponse));
      }
      else {
        $regPageRequest->campaignGuid = $campaignDetailsResponse->bodyResponse->campaignGuid;
        $regPageRequest->pageStory = json_decode($campaignDetailsResponse->bodyResponse->story);
      }
      return $this->justGivingClient->jgLoad()->Campaign->RegisterCampaignFundraisingPage($regPageRequest);
    }

    return $this->justGivingClient->jgLoad()->Page->CreateV2($regPageRequest);
  }

  /**
   * Preferred short name suggestion.
   *
   * @param string $preferredName
   *   Pass in preferred name / page title.
   *
   * @return mixed
   *   Response.
   */
  private function fundraisingPageUrlSuggestions(string $preferredName): mixed {
    return $this->justGivingClient->jgLoad()->Page->SuggestPageShortNames($preferredName);
  }

  /**
   * Checks if a short name is already taken.
   *
   * @param string $shortName
   *   Checks fundraiser page short name.
   *
   * @return mixed
   *   Response.
   */
  private function fundraisingPageExists(string $shortName): mixed {
    return $this->justGivingClient->jgLoad()->Page->IsShortNameRegistered($shortName);
  }

  /**
   * Get campaign details.
   *
   * @param string $shortName
   *   Campaign page short name.
   *
   * @return mixed
   *   Response or FALSE.
   */
  private function campaignDetails(string $shortName): mixed {
    try {
      // Get charity page short name for the next API call.
      $retrieveCharityResponse = $this->justGivingClient->jgLoad()->Charity->Retrieve($this->configuration['fundraiser_page_fields']['charity_id']);
      if (isset($retrieveCharityResponse->pageShortName)) {
        // Get campaign details for both charity & campaign short name.
        return $this->justGivingClient->jgLoad()->Campaign->RetrieveV2($shortName);
      }
    }
    catch (\Exception $e) {
      $this->getLogger()->error($e->getMessage());
      \Drupal::messenger()->addError($this->t('Something went wrong: @msg', ['@msg' => $e->getMessage()]));
    }
    return FALSE;
  }

}
