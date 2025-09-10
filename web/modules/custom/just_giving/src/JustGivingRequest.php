<?php

namespace Drupal\just_giving;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class PageCreate.
 */
class JustGivingRequest implements JustGivingRequestInterface {

  /**
   * Just giving account.
   *
   * Drupal\just_giving\JustGivingAccount definition.
   */
  protected JustGivingAccountInterface $justGivingAccount;

  /**
   * Just giving page.
   *
   * Drupal\just_giving\justGivingPage definition.
   */
  protected justGivingPageInterface $justGivingPage;

  /**
   * User account.
   */
  protected JustGivingAccountInterface $userAccount;

  /**
   * Route match.
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Constructs a new PageCreate object.
   */
  public function __construct(JustGivingAccountInterface $just_giving_account, justGivingPageInterface $just_giving_page, RouteMatchInterface $route_match) {
    $this->justGivingAccount = $just_giving_account;
    $this->justGivingPage = $just_giving_page;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritDoc}
   */
  public function createFundraisingPage(FormStateInterface $form_state): string {

    $checkExists = $this->justGivingAccount->checkAccountExists($form_state->getValue('email'));

    $userInfo = [
      'username' => $form_state->getValue('email'),
      'password' => $form_state->getValue('password'),
    ];

    if ($checkExists) {
      $this->userAccount = $this->justGivingAccount->retrieveAccount(
        $form_state->getValue('email'),
        $form_state->getValue('password')
      );
      $userInfo = $userInfo + [
        'first_name' => $this->userAccount->firstName,
        'last_name' => $this->userAccount->lastName,
      ];
    }
    else {
      $this->userAccount = $this->justGivingAccount->signupUser($form_state);
      $userInfo = $userInfo + [
        'first_name' => $form_state->getValue('first_name'),
        'last_name' => $form_state->getValue('last_name'),
      ];
    }

    $node = $this->routeMatch->getParameter('node');
    $this->justGivingPage->setPageInfo($node);
    $this->justGivingPage->setUserInfo($userInfo);

    return $this->justGivingPage->registerFundraisingPage();
  }

}
