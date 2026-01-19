<?php

namespace Drupal\wateraid_donation_sf3ds\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\wateraid_donation_sf3ds\Service\Sf3dsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Sf3ds - Post card processing controller.
 */
class CardFormReturn extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    private RequestStack $request,
    private Sf3dsService $sf3dsService,
    private ModuleExtensionList $moduleExtensionList,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('request_stack'),
      $container->get('wateraid_donation_sf3ds'),
      $container->get('extension.list.module')
    );
  }

  /**
   * Payment success page.
   *
   * @return array
   *   Confirmation render array.
   */
  public function success(): array {
    if ($webform_submission = $this->getSf3dsService()->getWebformSubmissionFromRoute()) {
      $data = $webform_submission->getData();
      $data['payment']['payment_response'] = 'sf3ds_success';
      $data['payment']['payment_result'] = 'sf3ds_success';
      $webform_submission->setData($data);
      $webform_submission->save();

      $confirmation_message = $webform_submission->getWebform()->getSetting('confirmation_message');
      $confirmation_image_uri = $this->moduleExtensionList->getPath('wateraid_donation_forms') . '/images/confirm-icon.png';

      return [
        'container' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'webform-confirmation',
              'wateraid-donations',
              'sf3ds',
            ],
          ],
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'h1',
            '#value' => $this->title(),
            '#attributes' => [
              'class' => ['webform-confirmation__title'],
            ],
          ],
          'confirmation_logo' => [
            '#theme' => 'image',
            '#uri' => $confirmation_image_uri,
            '#alt' => $this->t('Completed donation'),
            '#prefix' => '<div class="webform-confirmation__logo">',
            '#suffix' => '</div>',
          ],
          'confirmation_message' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['webform-confirmation__message'],
            ],
            '#markup' => $confirmation_message,
          ],
        ],
      ];
    }
    return [];
  }

  /**
   * Title callback.
   *
   * @return string
   *   The title.
   */
  public function title(): string {
    if ($webform_submission = $this->getSf3dsService()->getWebformSubmissionFromRoute()) {
      return $webform_submission->getWebform()->getSetting('confirmation_title');
    }
    return '';
  }

  /**
   * Access handler.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result decision.
   */
  public function access(): AccessResultInterface {

    if ($webform_submission = $this->getSf3dsService()->getWebformSubmissionFromRoute()) {

      // Access only allowed when referred from payment handler.
      if ($this->getSf3dsService()->getConfig()->get('validate_referer')) {
        if (!$referer = $this->getRequest()->getCurrentRequest()->server->get('HTTP_REFERER')) {
          $this->getLogger('wateraid_donation_sf3ds')->error('Referer is empty');
          return AccessResult::forbidden();
        }

        $url_parts = parse_url($referer);
        if (!array_key_exists('scheme', $url_parts) or !array_key_exists('host', $url_parts)) {
          $this->getLogger('wateraid_donation_sf3ds')->error("Cannot check referer scheme/host: for referrer '$referer', " . print_r($url_parts, TRUE));
          return AccessResult::forbidden();
        }
        $url = $url_parts['scheme'] . "://" . $url_parts['host'];
        $form_action_parts = parse_url($this->getSf3dsService()->getFormAction());
        $form_action_url = $form_action_parts['scheme'] . "://" . $form_action_parts['host'];
        if ($form_action_url != $url) {
          return AccessResult::forbidden();
        }
      }

      // Access no longer allowed after first success flagged.
      if ($webform_submission->getData()['payment']['payment_response'] ?? FALSE) {
        return AccessResult::forbidden();
      }

      // Access allowed yet-to-be successful submission.
      return AccessResult::allowed();
    }

    // Webform not found, deny access.
    else {
      return AccessResult::forbidden();
    }
  }

  /**
   * Get 'wateraid_donation_sf3ds' service.
   *
   * @return \Drupal\wateraid_donation_sf3ds\Service\Sf3dsService
   *   The 'wateraid_donation_sf3ds' service.
   */
  public function getSf3dsService(): Sf3dsService {
    return $this->sf3dsService;
  }

  /**
   * Get 'request_stack' service.
   *
   * @return \Symfony\Component\HttpFoundation\RequestStack
   *   The 'request_stack' service.
   */
  public function getRequest(): RequestStack {
    return $this->request;
  }

}
