<?php

namespace Drupal\wateraid_donation_forms\Controller;

use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Utility\Token;
use Drupal\sharethis\SharethisManagerInterface;
use Drupal\wateraid_donation_forms\DonationConstants;
use Drupal\wateraid_donation_forms\FallbackPluginManager;
use Drupal\wateraid_donation_forms\PaymentTypePluginManager;
use Drupal\webform\Controller\WebformEntityController;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Allows manipulation of the response object when performing a redirect.
 */
class WebformController extends WebformEntityController {

  /**
   * The token service.
   */
  protected Token $token;

  /**
   * The page cache disabling policy.
   */
  protected KillSwitch $pageCacheKillSwitch;

  /**
   * The Payment Type Plugin service.
   */
  protected PaymentTypePluginManager $paymentTypePluginManager;

  /**
   * The sharethis manager.
   */
  protected SharethisManagerInterface $sharethisManager;

  /**
   * The Fallback Plugin manager.
   */
  protected FallbackPluginManager $fallbackPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->token = $container->get('token');
    $instance->pageCacheKillSwitch = $container->get('page_cache_kill_switch');
    $instance->paymentTypePluginManager = $container->get('plugin.manager.payment_type');
    $instance->sharethisManager = $container->get('sharethis.manager');
    $instance->fallbackPluginManager = $container->get('plugin.manager.fallback');
    return $instance;
  }

  /**
   * Returns a webform error page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   *
   * @return mixed[]
   *   A render array representing a webform error page
   */
  public function error(Request $request, ?WebformInterface $webform = NULL): array {
    $build['message'] = [
      '#markup' => $this->t('There was an error completing your donation. A payment may have been taken, please check before trying again.'),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function confirmation(Request $request, ?WebformInterface $webform = NULL, ?WebformSubmissionInterface $webform_submission = NULL): array|RedirectResponse {
    $build = parent::confirmation($request, $webform, $webform_submission);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $build['#webform'];
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $build['#webform_submission'];

    $this->pageCacheKillSwitch->trigger();

    // Check if the referer is a WaterAid domain.
    $referer = FALSE;
    foreach (['wateraid.org', 'wateraidglobal.acsitefactory.com'] as $search) {
      // If the referer hasn't been found, check the next domain.
      if ($referer === FALSE) {
        $referer = isset($_SERVER['HTTP_REFERER']) && !str_contains($_SERVER['HTTP_REFERER'], $search);
      }
    }

    $viewed_page = $_SESSION['view_page'] ?? NULL;

    // If the referer isn't correct or page has been viewed, redirect.
    if (!$referer || !$webform_submission || $viewed_page == $webform_submission->getToken()) {
      $webform_submission = NULL;
      return new RedirectResponse('/');
    }

    // Set the view page variable, so we know to redirect if reloaded.
    $_SESSION['view_page'] = $webform_submission->getToken();

    // Process donation form specific markup if applicable.
    if (_wateraid_donation_forms_is_donation_form($webform)) {
      // Apply default confirmation page settings from Webform confirmation &
      // 3rd party settings.
      $build = $this->applyDonationConfirmationSettings($request, $webform, $webform_submission, $build);
      // Override confirmation page settings from Confirmation Page Webform
      // handler.
      $build = $this->applyDonationConfirmationPageHandlerSettings($request, $webform, $webform_submission, $build);
    }

    // If share text has been specified, override it here. (This could
    // technically work for non-donation forms as well).
    if ($og_title = $webform->getThirdPartySetting('wateraid_donation_forms', 'social_share_text')) {
      $title_tag = [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'og:title',
          'content' => $this->token->replace($og_title, ['webform_submission' => $webform_submission]),
        ],
      ];
      $build['#attached']['html_head'][] = [$title_tag, 'og_title'];
    }

    // Always override the url with the form url (we never want to link directly
    // to confirmation pages).
    $url_tag = [
      '#tag' => 'meta',
      '#attributes' => [
        'name' => 'og:url',
        'content' => $webform->toUrl('canonical', ['absolute' => TRUE])->toString(),
      ],
    ];

    $build['#attached']['html_head'][] = [$url_tag, 'og_url'];

    return $build;
  }

  /**
   * Provide donation specific functionality.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The submission.
   * @param mixed[] $build
   *   The Drupal build array.
   *
   * @return mixed[]
   *   the updated build array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function applyDonationConfirmationSettings(Request $request, WebformInterface $webform, WebformSubmissionInterface $webform_submission, array $build): array {

    // @note That the markup is added directly on $build['#webform_submission'].
    // This is for theme function "webform_confirmation" which only adds those
    // variables.
    $prefix = DonationConstants::DONATION_PREFIX;
    $data = $webform_submission->getData();

    if ($this->moduleHandler()->moduleExists('datalayer')) {
      datalayer_add([
        'donationId' => $webform_submission->id(),
        'donationFormId' => $webform_submission->getWebform()->id(),
        'donationCurrency' => $data[$prefix . 'currency'] ?? NULL,
        'donationAmount' => $data[$prefix . 'amount'] ?? NULL,
        'donationDate' => $data[$prefix . 'date'] ?? NULL,
        'donationFrequency' => $data[$prefix . 'frequency'] ?? NULL,
        'donationPaymentMethod' => $data[$prefix . 'payment_method'] ?? NULL,
        'donationPaymentType' => $data[$prefix . 'payment_type'] ?? NULL,
        'donationFundCode' => $data[$prefix . 'fund_code'] ?? NULL,
        'donationPackageCode' => $data[$prefix . 'package_code'] ?? NULL,
      ]);
    }

    // Set default title.
    $build['#webform_submission']->waConfirmationTitle = [
      '#markup' => $webform->getSettings()['confirmation_title'] ?? NULL,
    ];

    // Confirmation logo.
    $confirmation_image_uri = $webform->getThirdPartySetting('wateraid_donation_forms', 'confirmation_image_url');
    if (empty($confirmation_image_uri)) {
      $confirmation_image_uri = \Drupal::service('extension.list.module')->getPath('wateraid_donation_forms') . '/images/confirm-icon.png';
    }

    $build['#webform_submission']->waConfirmationLogo = [
      '#theme' => 'image',
      '#uri' => $confirmation_image_uri,
      '#alt' => $this->t('Completed donation'),
    ];

    // Donation upsell image.
    if ($upsellImageField = $webform->getThirdPartySetting('wateraid_donation_forms', 'donation_upsell_confirmation')) {
      $upsell_image_id = ($upsellImageField['donation_upsell_image'][0]) ?? NULL;
      if (!empty($upsell_image_id)) {
        $image_file = \Drupal::entityTypeManager()->getStorage('file')->load($upsell_image_id);
        $upsell_image_url = $image_file->createFileUrl();

        $build['#webform_submission']->waDonationUpsellImage = [
          '#theme' => 'image',
          '#uri' => $upsell_image_url,
        ];
      }
    }

    // Donation upsell text.
    if ($upsellTextField = $webform->getThirdPartySetting('wateraid_donation_forms', 'donation_upsell_confirmation')) {
      $message_content = $upsellTextField['donation_upsell_text']['value'];
      $message_format = $webform->getThirdPartySetting('wateraid_donation_forms', 'donation_upsell_confirmation')['donation_upsell_text']['format'];
      $build['#webform_submission']->waDonationUpsellText = [
        '#markup' => check_markup($this->token->replace($message_content, ['webform_submission' => $webform_submission]), $message_format),
      ];
    }

    // Donation upsell shop code.
    if ($upsellCodeField = $webform->getThirdPartySetting('wateraid_donation_forms', 'donation_upsell_confirmation')) {
      $upsellCode = $upsellCodeField['donation_upsell_discount_widget']['donation_upsell_discount_code'];
      $build['#webform_submission']->waDonationUpsellCode = [
        '#markup' => $upsellCode,
      ];
    }

    // Donation upsell exclusions.
    if ($exclusionsField = $webform->getThirdPartySetting('wateraid_donation_forms', 'donation_upsell_confirmation')) {
      $exclusions = $exclusionsField['donation_upsell_discount_widget']['donation_upsell_exclusions'];
      $build['#webform_submission']->waDonationUpsellExclusions = [
        '#markup' => $exclusions,
      ];
    }

    // Set default message.
    $build['#webform_submission']->waConfirmationMessage = [
      '#markup' => $webform->getSettings()['confirmation_message'] ?? NULL,
    ];

    // Donation details summary.
    $message_content = $webform->getThirdPartySetting('wateraid_donation_forms', 'donation_confirmation')['value'];
    $message_format = $webform->getThirdPartySetting('wateraid_donation_forms', 'donation_confirmation')['format'];
    $build['#webform_submission']->waDonationConfirmation = [
      '#markup' => check_markup($this->token->replace($message_content, ['webform_submission' => $webform_submission]), $message_format),
    ];

    // Payment type specific markup.
    if (!empty($data[$prefix . 'payment_type'])) {
      $payment_type = $data[$prefix . 'payment_type'];

      /** @var \Drupal\wateraid_donation_forms\PaymentTypeInterface $payment_type_plugin */
      $payment_type_plugin = $this->paymentTypePluginManager->createInstance($payment_type);
      $message_content = $payment_type_plugin->getConfirmationPageMarkup($webform_submission);
      $build['#webform_submission']->waDonationPaymentTypeDetails = [
        '#markup' => check_markup($this->token->replace($message_content, ['webform_submission' => $webform_submission]), $message_format),
      ];

      // Bank transfer details.
      if ($payment_type == 'bank_transfer') {
        $message_content = $webform->getThirdPartySetting('wateraid_donation_forms', 'bank_transfer_instructions')['value'];
        $message_format = $webform->getThirdPartySetting('wateraid_donation_forms', 'bank_transfer_instructions')['format'];
        $build['#webform_submission']->waDonationBankAccountDetails = [
          '#markup' => check_markup($this->token->replace($message_content, ['webform_submission' => $webform_submission]), $message_format),
        ];
      }
    }

    // Only display Gift Aid if tick box ticked.
    $gift_aid = $data['gift_aid'] ?? [];
    if (!empty($gift_aid['opt_in']) && $gift_aid['opt_in'] === 'Yes') {
      $message_content = $webform->getThirdPartySetting('wateraid_donation_forms', 'gift_aid_confirmation')['value'];
      $message_format = $webform->getThirdPartySetting('wateraid_donation_forms', 'gift_aid_confirmation')['format'];
      $build['#webform_submission']->waGiftAidConfirmation = [
        '#markup' => check_markup($this->token->replace($message_content, ['webform_submission' => $webform_submission]), $message_format),
      ];
    }
    else {
      $build['#webform_submission']->waGiftAidConfirmation = NULL;
    }

    // Confirmation CTA markup.
    $message_content = $webform->getThirdPartySetting('wateraid_donation_forms', 'after_confirmation_cta')['value'];
    $message_format = $webform->getThirdPartySetting('wateraid_donation_forms', 'after_confirmation_cta')['format'];
    $build['#webform_submission']->waAfterConfirmationMessage = [
      '#markup' => check_markup($this->token->replace($message_content, ['webform_submission' => $webform_submission]), $message_format),
    ];

    // Confirmation footer markup.
    $message_content = $webform->getThirdPartySetting('wateraid_donation_forms', 'after_confirmation_footer')['value'];
    $message_format = $webform->getThirdPartySetting('wateraid_donation_forms', 'after_confirmation_footer')['format'];
    $build['#webform_submission']->waAfterConfirmationFooter = [
      '#markup' => check_markup($this->token->replace($message_content, ['webform_submission' => $webform_submission]), $message_format),
    ];

    // Create sharethis block dynamically and add to content.
    $social_title = $webform->getThirdPartySetting('wateraid_donation_forms', 'social_share_title')['value'];
    $st_js = $this->sharethisManager->sharethisIncludeJs();
    $markup = $this->sharethisManager->blockContents();
    $content = [
      '#theme' => 'sharethis_block',
      '#content' => $markup,
      '#attached' => [
        'library' => [
          'sharethis/sharethispickerexternalbuttonsws',
          'sharethis/sharethispickerexternalbuttons',
          'sharethis/sharethis',
        ],
        'drupalSettings' => [
          'sharethis' => $st_js,
        ],
      ],
    ];

    $content = \Drupal::service('renderer')->render($content);
    $build['#webform_submission']->waSocialShare = [
      '#markup' => '<span>' . $social_title . '</span>' . $content,
    ];

    return $build;
  }

  /**
   * Provide ConfirmationPageWebformHandler specific functionality.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   * @param mixed[] $build
   *   The Drupal build array.
   *
   * @return mixed[]
   *   The updated build array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @see \Drupal\wateraid_donation_forms\Plugin\WebformHandler\ConfirmationPageWebformHandler
   * @see \Drupal\wateraid_donation_forms\FallbackWebformHandlerTrait
   * /
   */
  protected function applyDonationConfirmationPageHandlerSettings(Request $request, WebformInterface $webform, WebformSubmissionInterface $webform_submission, array $build): array {

    /** @var \Drupal\webform\Plugin\WebformHandlerPluginCollection $handlers */
    $handlers = $webform->getHandlers('confirmation_page');
    $handler_ids = $handlers->getInstanceIds();

    foreach ($handler_ids as $handler_id) {
      /** @var \Drupal\wateraid_donation_forms\Plugin\WebformHandler\ConfirmationPageWebformHandler $handler */
      $handler = $handlers->get($handler_id);
      // Look for a handler that passes all the condition checks.
      if ($handler
        && $handler->isEnabled()
        && $handler->checkConditions($webform_submission) !== FALSE
        && $handler->paymentFrequencyMatches($webform_submission) !== FALSE
      ) {
        $configuration = $handler->getConfiguration();
        // Replace markup if details are given.
        if (!empty($configuration['settings']['confirmation_title'])) {
          $build['#webform_submission']->waConfirmationTitle = [
            '#markup' => $this->tokenManager->replace($configuration['settings']['confirmation_title'], $webform_submission),
          ];
        }
        // Check on Fallback plugin.
        $fallback_applied = FALSE;
        if (!empty($configuration['settings']['fallback_plugin'])) {
          /** @var \Drupal\wateraid_donation_forms\FallbackInterface $fallback_plugin */
          $fallback_plugin = $this->fallbackPluginManager->createInstance($configuration['settings']['fallback_plugin']);
          // Process "fallback_message" on "confirmation_message".
          if ($fallback_plugin->isApplicable($webform_submission)) {
            $build['#webform_submission']->waConfirmationMessage = [
              '#markup' => $this->tokenManager->replace($configuration['settings']['fallback_message'] ?? NULL, $webform_submission),
            ];
            $fallback_applied = TRUE;
          }
        }
        // Check on Confirmation message.
        if ($fallback_applied !== TRUE && !empty($configuration['settings']['confirmation_message'])) {
          $build['#webform_submission']->waConfirmationMessage = [
            '#markup' => $this->tokenManager->replace($configuration['settings']['confirmation_message'], $webform_submission),
          ];
        }
        // Donation confirmation overrides.
        if (!empty($configuration['settings']['donation_confirmation'])) {
          $build['#webform_submission']->waDonationConfirmation = [
            '#markup' => $this->tokenManager->replace($configuration['settings']['donation_confirmation'], $webform_submission),
          ];
        }
        // We only care for 1 configured handler that passed the conditional
        // states.
        break;
      }
    }

    return $build;
  }

}
