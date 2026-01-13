<?php

namespace Drupal\cloudflare_redirect\StackMiddleware;

use Drupal\page_cache\StackMiddleware\PageCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class StaticCache.
 *
 * This class is required to prevent certain pages being cached before
 * EventSubscriber is executed.
 * (Setting EventSubscriber priority does not help as other events are
 * prohibited as a result.)
 *
 * @package Drupal\cloudflare_redirect
 */
class StaticCache extends PageCache {

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {

    // Invalidate page cache for home and donate pages when country or referer
    // headers are set. Allows RedirectSubscriber to further check validity of
    // request and to decide whether to redirect to country site.
    // @todo migrate functionality from RedirectSubmit to here.
    $ignore_page_cache = FALSE;
    $request_uri = $request->getRequestUri();
    if ($request_uri === '/' || $request_uri === '/donate') {
      $headers = $request->headers->all();
      if (!isset($headers['referer']) && !empty($headers['cf-ipcountry'][0])) {
        $ignore_page_cache = TRUE;
      }
    }

    return $ignore_page_cache ? $this->pass($request, $type, $catch) : parent::handle($request, $type, $catch);
  }

}
