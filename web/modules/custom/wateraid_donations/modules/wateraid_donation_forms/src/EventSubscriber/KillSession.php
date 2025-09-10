<?php

namespace Drupal\wateraid_donation_forms\EventSubscriber;

use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Session\SessionManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * KillSession Event subscriber.
 *
 * @package Drupal\wateraid_donation_forms
 */
class KillSession implements EventSubscriberInterface {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   */
  protected AccountProxy $currentUser;

  /**
   * Drupal\Core\Session\SessionManager definition.
   */
  protected SessionManager $sessionManager;

  /**
   * Constructs a new KillSession object.
   */
  public function __construct(AccountProxy $current_user, SessionManager $session_manager) {
    $this->currentUser = $current_user;
    $this->sessionManager = $session_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events['kernel.response'] = ['response'];

    return $events;
  }

  /**
   * Kill the session if it's anonymous and contains webform_submissions.
   *
   * We do this on the response event, so anything requiring that session
   * variable (e.g. the wateraid_webform_encrypt module) will have already got
   * a chance to use it.
   */
  public function response(Event $event): void {
    if (!empty($_SESSION['webform_submissions']) && $this->currentUser->isAnonymous() && $this->sessionManager->isStarted()) {
      $this->sessionManager->destroy();
      // Also manually clear session. to prevent uninitialised session warning.
      // @see https://stackoverflow.com/questions/6472123.
      $_SESSION = [];
    }
  }

}
