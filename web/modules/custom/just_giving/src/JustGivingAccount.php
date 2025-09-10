<?php

namespace Drupal\just_giving;

use Drupal\Core\Form\FormStateInterface;

/**
 * Creates a Just Giving Account.
 */
class JustGivingAccount implements JustGivingAccountInterface {

  /**
   * The Just Giving client.
   */
  protected JustGivingClientInterface $justGivingClient;

  /**
   * The Just Giving address.
   */
  protected \Address $justGivingAddress;

  /**
   * Just Giving address details.
   *
   * @var mixed[]
   */
  protected array $jgAddressDetails;

  /**
   * The Just Giving account request.
   */
  protected \CreateAccountRequest $justGivingAccRequest;

  /**
   * The Account details.
   *
   * @var mixed[]
   */
  protected array $jgAccountDetails;

  /**
   * Constructs a new JustGivingAccount object.
   */
  public function __construct(JustGivingClientInterface $just_giving_client) {
    $this->justGivingClient = $just_giving_client;
  }

  /**
   * {@inheritDoc}
   */
  public function setJgAddressDetails(array $jgAddressDetails): void {
    $this->jgAddressDetails = $jgAddressDetails;
  }

  /**
   * {@inheritDoc}
   */
  public function setJgAccountDetails(array $jgAccountDetails): void {
    $this->jgAccountDetails = $jgAccountDetails;
  }

  /**
   * {@inheritDoc}
   */
  public function createAccount(): mixed {
    $jg_account_request = $this->createAccountRequest();
    return $this->justGivingClient->jgLoad()->Account->create($jg_account_request);
  }

  /**
   * {@inheritDoc}
   */
  public function checkAccountExists(string $user_email): mixed {
    return $this->justGivingClient->jgLoad()->Account->IsEmailRegistered($user_email);
  }

  /**
   * {@inheritDoc}
   */
  public function validateAccount($email, $password): mixed {
    $credentials = [
      'email' => $email,
      'password' => $password,
    ];
    return $this->justGivingClient->jgLoad()->Account->IsValid($credentials);
  }

  /**
   * Sets a password reminder on the account.
   *
   * @param string $email
   *   The email to identify the account.
   *
   * @return bool
   *   Whether the reminder was successfully set.
   */
  public function passwordReminder(string $email): bool {
    $reminderResult = $this->justGivingClient->jgLoad()->Account->RequestPasswordReminder($email);
    if (isset($reminderResult['0']->id) && $reminderResult['0']->id == "AccountNotFound") {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Gets an account via the Just Giving client.
   *
   * @param string $email
   *   The email for the account.
   * @param string $password
   *   The password for the account.
   *
   * @return mixed
   *   The account details.
   */
  public function retrieveAccount(string $email, string $password): mixed {
    $this->justGivingClient->setUsername($email);
    $this->justGivingClient->setPassword($password);
    return $this->justGivingClient->jgLoad()->Account->AccountDetails();
  }

  /**
   * The form submit.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal form state.
   *
   * @return mixed
   *   The account created message.
   */
  public function signupUser(FormStateInterface $form_state): mixed {

    $jgAddressDetails = [
      'line1' => $form_state->getValue('first_line_of_address'),
      'line2' => $form_state->getValue('second_line_of_address'),
      'town_or_city' => $form_state->getValue('town_or_city'),
      'county_or_state' => $form_state->getValue('county_or_state'),
      'country' => $form_state->getValue('country'),
      'postcode_or_zipcode' => $form_state->getValue('postcode'),
    ];
    $jgAccountDetails = [
      'reference' => $form_state->getValue('reference'),
      'title' => $form_state->getValue('title'),
      'first_name' => $form_state->getValue('first_name'),
      'last_name' => $form_state->getValue('last_name'),
      'email' => $form_state->getValue('email'),
      'password' => $form_state->getValue('password'),
      'accept' => $form_state->getValue('accept_terms_and_conditions'),
    ];

    $this->setJgAddressDetails($jgAddressDetails);
    $this->setJgAccountDetails($jgAccountDetails);

    return $this->createAccount();
  }

  /**
   * Creates an Account Request.
   *
   * @return \CreateAccountRequest
   *   The account request.
   */
  private function createAccountRequest(): \CreateAccountRequest {

    $this->justGivingAccRequest = new \CreateAccountRequest();
    $this->justGivingAccRequest->reference = $this->jgAccountDetails['reference'];
    $this->justGivingAccRequest->title = $this->jgAccountDetails['title'];
    $this->justGivingAccRequest->firstName = $this->jgAccountDetails['first_name'];
    $this->justGivingAccRequest->lastName = $this->jgAccountDetails['last_name'];
    $this->justGivingAccRequest->email = $this->jgAccountDetails['email'];
    $this->justGivingAccRequest->password = $this->jgAccountDetails['password'];
    $this->justGivingAccRequest->acceptTermsAndConditions = $this->jgAccountDetails['accept'];
    $this->justGivingAccRequest->address = $this->buildAddress();

    return $this->justGivingAccRequest;
  }

  /**
   * Builds the account address.
   *
   * @return \Address
   *   The account address.
   */
  private function buildAddress(): \Address {

    $this->justGivingAddress = new \Address();
    $this->justGivingAddress->line1 = $this->jgAddressDetails['line1'];
    $this->justGivingAddress->line2 = $this->jgAddressDetails['line2'];
    $this->justGivingAddress->townOrCity = $this->jgAddressDetails['town_or_city'];
    $this->justGivingAddress->countyOrState = $this->jgAddressDetails['county_or_state'];
    $this->justGivingAddress->country = $this->jgAddressDetails['country'];
    $this->justGivingAddress->postcodeOrZipcode = $this->jgAddressDetails['postcode_or_zipcode'];

    return $this->justGivingAddress;
  }

}
