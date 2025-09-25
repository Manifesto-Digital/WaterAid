<?php

namespace Drupal\just_giving\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\just_giving\JustGivingAccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns a test string.
 */
class JustGivingController extends ControllerBase {

  /**
   * Drupal\just_giving\JustGivingClient definition.
   */
  protected JustGivingAccountInterface $justGivingAccount;

  /**
   * Constructs a new AccountController object.
   *
   * @param \Drupal\just_giving\JustGivingAccountInterface $just_giving_account
   *   The just giving account service.
   */
  public function __construct(JustGivingAccountInterface $just_giving_account) {
    $this->justGivingAccount = $just_giving_account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('just_giving.account')
    );
  }

  /**
   * Hello.
   *
   * @param string $name
   *   A username string.
   *
   * @return string[]
   *   Return Hello string.
   */
  public function accountCreate(string $name): array {
    return $this->justGivingAccount->createAccount();
  }

}
