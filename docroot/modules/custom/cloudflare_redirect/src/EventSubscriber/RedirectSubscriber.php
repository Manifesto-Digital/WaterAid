<?php

namespace Drupal\cloudflare_redirect\EventSubscriber;

use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Path\PathMatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * CRedirects based on country.
 *
 * @package Drupal\cloudflare_redirect
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * RedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   The path matcher object.
   */
  public function __construct(protected readonly PathMatcherInterface $pathMatcher) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => [
        ['updatePageHeaders'],
      ],
    ];
  }

  /**
   * Set headers on specific pages.
   *
   * Set vary header for home and donate to help with cache configure.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   Kernel event.
   */
  public function updatePageHeaders(ResponseEvent $event): void {
    $request = $event->getRequest();
    $response = $event->getResponse();

    if ($request->getMethod() === 'GET' && ($this->pathMatcher->isFrontPage() || $request->getRequestUri() === '/donate')) {
      $response->setVary(['cf-ipcountry', 'referer']);
      $response->send();
    }
  }

}
