<?php

namespace Drupal\just_giving\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\just_giving\JustGivingAccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AccountController.
 */
class JustGivingAccountController extends ControllerBase {

  /**
   * Drupal\just_giving\JustGivingClient definition.
   */
  protected JustGivingAccountInterface $justGivingAccount;

  /**
   * Constructs a new AccountController object.
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
   * Account create.
   *
   * @param string $name
   *   Name parameter.
   *
   * @return string[]
   *   Return Hello string.
   */
  public function accountCreate(string $name): array {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: hello with parameter(s): $name'),
    ];
  }

}
