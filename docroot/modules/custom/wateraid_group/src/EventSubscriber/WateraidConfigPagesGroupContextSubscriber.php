<?php

declare(strict_types=1);

namespace Drupal\wateraid_group\EventSubscriber;

use Drupal\config_pages\ConfigPagesTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\wateraid_group\Plugin\ConfigPagesContext\WateraidSiteGroupContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for config_pages custom 'wateraid_site' group context.
 */
final class WateraidConfigPagesGroupContextSubscriber implements EventSubscriberInterface {
  use MessengerTrait;

  /**
   * Constructs a WateraidConfigPagesGroupContextSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Kernel response event handler.
   *
   * Essentially, by adding the config_pages context for group, type
   * 'wateraid_site', it is still possible to save a config_pages entity without
   * group context. This ensures that we are redirected to a config_page for
   * a group, rather than without, if the context has been applied to a
   * config_page type.
   */
  public function onResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest() || !$event->getRequest()->isMethod('GET')) {
      return;
    }
    $request = $event->getRequest();
    $route_name = $request->attributes->get('_route');
    if ($route_name !== 'config_pages.add_form') {
      return;
    }
    $config_page_type = $request->attributes->get('config_pages_type');
    if ($config_page_type instanceof ConfigPagesTypeInterface) {
      $has_wateraid_site_group_context = $config_page_type->context['group']['wateraid_site_group'];
      if ($has_wateraid_site_group_context && empty($request->query->get(WateraidSiteGroupContext::GROUP_ID_QUERY_PARAM))) {
        // Just query the first group of this type, so we always have a context.
        $group_query = $this->entityTypeManager->getStorage('group')->getQuery();
        $group_query->condition('type', 'wateraid_site')
          ->accessCheck(FALSE)
          ->condition('status', 1)
          ->range(0, 1)
          ->sort('id');
        $group = $group_query->execute();
        if (!empty($group)) {
          // Clear messages from the old response.
          $this->messenger()->deleteAll();
          // Re-build URL and add query param.
          $route_params = $request->attributes->get('_route_params')['_raw_variables']->all() ?? [];
          $query_params = $request->query->all();
          $query_params[WateraidSiteGroupContext::GROUP_ID_QUERY_PARAM] = reset($group)[0];

          $url = Url::fromRoute($route_name, $route_params, [
            'query' => $query_params,
            'absolute' => TRUE,
          ])->toString();

          $response = new TrustedRedirectResponse($url);
          $event->setResponse($response);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => ['onResponse'],
    ];
  }

}
