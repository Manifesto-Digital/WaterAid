<?php

namespace Drupal\wateraid_donation_stripe\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\anonymous_token\Access\AnonymousCsrfTokenGenerator;
use Drupal\wateraid_donation_stripe\WaterAidWebformStripeService;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stripe PaymentIntent Controller.
 */
class StripePaymentIntentController extends ControllerBase {

  /**
   * The WaterAidWebformStripeService.
   */
  protected WaterAidWebformStripeService $webformStripeService;

  /**
   * Logger service.
   */
  protected LoggerChannelInterface $logger;

  /**
   * The UUID service.
   */
  protected UuidInterface $uuidService;

  /**
   * The CSRF token generator.
   */
  protected AnonymousCsrfTokenGenerator $csrfTokenGenerator;

  /**
   * Constructs a new StripePaymentIntentController object.
   *
   * @param \Drupal\wateraid_donation_stripe\WaterAidWebformStripeService $webform_stripe_service
   *   The stripe api service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   A logger instance.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\anonymous_token\Access\AnonymousCsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   */
  public function __construct(WaterAidWebformStripeService $webform_stripe_service, LoggerChannelInterface $logger, UuidInterface $uuid_service, AnonymousCsrfTokenGenerator $csrf_token) {
    $this->webformStripeService = $webform_stripe_service;
    $this->logger = $logger;
    $this->uuidService = $uuid_service;
    $this->csrfTokenGenerator = $csrf_token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('wateraid_webform_stripe'),
      $container->get('logger.channel.wateraid_donation_stripe'),
      $container->get('uuid'),
      $container->get('anonymous_token.csrf_token')
    );
  }

  /**
   * Create new payment Intent.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A new json response object contains the details of paymentIntent.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\wateraid_donation_forms\Exception\PaymentException
   */
  public function createIntent(Request $request): JsonResponse {

    // Create a new CSRF token as the seed will have been cleared upon entering
    // this route.
    $csrf_token = $this->csrfTokenGenerator->get('wateraid-donation-v2/stripe/sca/payment_intent');

    if ($request->isXmlHttpRequest() === FALSE) {
      // Error out in this case.
      return new JsonResponse([
        'error' => ['code' => Response::HTTP_FORBIDDEN],
      ], Response::HTTP_FORBIDDEN);
    }

    $content = $request->getContent();
    if (!empty($content)) {
      $data = Json::decode($content);
      // Quick fix awaiting the CSRF fix for anonymous users via WMS-929.
      $frequency = $data['donation_details']['frequency'];
      $min_amount = $data['donation_details']['amounts'][$frequency]['minimum_amount'];
      /** @var \Drupal\webform\WebformInterface $webform */
      $webform = $this->entityTypeManager()->getStorage('webform')->load($data['webform_id']);
      if ($data['donation_details']['amount'] < $min_amount || !$webform) {
        // Error out in this case.
        return new JsonResponse([
          'error' => [
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'Invalid request.',
          ],
        ], Response::HTTP_BAD_REQUEST);
      }

      // Handle the subscription.
      if ($data['donation_details']['frequency'] === 'recurring' && $data['donation_details']['paymentMethod'] === 'stripe_subscription') {
        return $this->subscriptionHandler($data, $csrf_token, $webform);
      }
      // Handle fixed period schedules.
      if ($data['donation_details']['frequency'] === 'fixed_period') {
        return $this->fixedPeriodHandler($data, $csrf_token, $webform);
      }
      // Or handle the one-off donations.
      return $this->oneOffPaymentHandler($data, $csrf_token, $webform);
    }
    // Error out in this case.
    return new JsonResponse([
      'error' => [
        'code' => 400,
        'message' => 'No body contents',
      ],
    ], 200);
  }

  /**
   * Metadata normaliser.
   *
   * @param mixed[] $data
   *   Data input.
   *
   * @return mixed[]
   *   Normalised metadata.
   */
  private function metadataBuilder(array $data): array {

    // Build metadata set.
    if (is_array($data['donor_meta_details'])) {
      $donor_details = $data['donor_meta_details'];
    }
    else {
      // This is the contact configuration details on each web form.
      // @see Drupal\wateraid_donation_forms\Plugin\WebformHandler.
      $donor_details = Json::decode($data['donor_meta_details']);
    }

    // Fallback initialization for empty customer email details.
    if (empty($donor_details['customer_email'])) {
      $donor_details['customer_email'] = $data['payment_method_id'];
    }

    if (!empty($data['donation_details']['contactPhone'])) {
      $donor_details['customer_phone'] = $data['donation_details']['contactPhone'];
    }
    if (!empty($data['donation_details']['contactEmail'])) {
      $donor_email = $data['donation_details']['contactEmail'];
      $donor_details['customer_email'] = $donor_email;
    }
    if (!empty($data['donation_details']['contactName'])) {
      $donor_details += $data['donation_details']['contactName'];
    }

    return $donor_details;
  }

  /**
   * Fixed period handler.
   *
   * @param mixed[] $data
   *   The payment request data Array.
   * @param string $csrf_token
   *   CSRF token.
   * @param \Drupal\webform\WebformInterface $webform
   *   a Webform instance.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   SubscriptionSchedule from the Subscription obj.
   *
   * @throws \Drupal\wateraid_donation_forms\Exception\PaymentException
   *   If payment failed.
   */
  private function fixedPeriodHandler(array $data, string $csrf_token, WebformInterface $webform): JsonResponse {

    try {

      $donor_details = $this->metadataBuilder($data);

      // Creating customer.
      $description = $this->t('Customer for :email', [':email' => $donor_details['customer_email']]);

      $customer_params = [
        'description' => $description,
        'email' => $donor_details['customer_email'],
        'payment_method' => $data['payment_method_id'],
        'metadata' => $donor_details,
        'invoice_settings' => [
          'default_payment_method' => $data['payment_method_id'],
        ],
      ];

      $stripe = $this->webformStripeService->getStripeClient($webform);

      try {
        $result = $stripe->customers->create($customer_params);
        $customer_id = $result['id'];
      }
      catch (\Exception $e) {
        $context = [
          'object' => 'Customer',
          'method' => 'create',
          'params' => $customer_params,
        ];
        $error_message = $this->t("Customer creation Failed! \r\n Error: @exception \r\n Context: @context", [
          '@exception' => $e->getMessage(),
          '@context' => Json::encode($context),
        ]);
        $this->logger->info($error_message);
        // Return error message.
        return new JsonResponse([
          'error' => [
            'code' => $e->getHttpStatus(),
            'message' => $e->getMessage(),
            'token' => $csrf_token,
          ],
        ], 200);
      }

      $params_schedule = [
        'customer' => $customer_id,
        'start_date' => 'now',
        'end_behavior' => 'cancel',
        'phases' => [
          [
            'items' => [
              [
                'price' => $data['donation_details']['fixedPrice'],
                'quantity' => 1,
              ],
            ],
            'iterations' => $data['donation_details']['fixedDuration'],
          ],
        ],
      ];

      // Pass the UUID as a header for idempotency_key.
      $options = [
        'idempotency_key' => $data['idempotency_key'],
      ];

      $schedule = $stripe->subscriptionSchedules->create($params_schedule, $options);

      return new JsonResponse([
        'subscriptionSchedule' => $schedule,
        'token' => $csrf_token,
      ], 200);

    }
    catch (\Exception $e) {
      $context = [
        'object' => 'subsciptionSchedule',
        'method' => 'create',
        'params' => $params_schedule,
      ];
      $error_message = $this->t("Payment Failed! \r\n Error: @exception \r\n Context: @context", [
        '@exception' => $e->getMessage(),
        '@context' => Json::encode($context),
      ]);
      $this->logger->info($error_message);
      // Return error message.
      return new JsonResponse([
        'error' => [
          'code' => $e->getHttpStatus(),
          'message' => $e->getMessage(),
          'token' => $csrf_token,
        ],
      ], 200);
    }
  }

  /**
   * One Off handler.
   *
   * @param mixed[] $data
   *   The payment request data Array.
   * @param string $csrf_token
   *   CSRF token.
   * @param \Drupal\webform\WebformInterface $webform
   *   a Webform instance.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Payment intent from the Subscription obj.
   *
   * @throws \Drupal\wateraid_donation_forms\Exception\PaymentException
   *   If payment failed.
   */
  private function oneOffPaymentHandler(array $data, string $csrf_token, WebformInterface $webform): JsonResponse {

    $donor_details = $this->metadataBuilder($data);

    $description = $this->t('Payment by Stripe from :donor_email', [
      ':donor_email' => $donor_details['customer_email'],
    ]);

    $params = [
      // Important braces wrapping the multiplying before casting to integer!
      'amount' => $data['donation_details']['amount'] * 100,
      'currency' => strtolower($data['donation_details']['currency']),
      'payment_method_types' => ['card'],
      'payment_method' => $data['payment_method_id'],
      'description' => $description,
      'setup_future_usage' => 'off_session',
      'metadata' => $donor_details,
      'expand' => ['charges.data'],
    ];

    // Pass the UUID as a header for idempotency_key.
    $options = [
      'idempotency_key' => $data['idempotency_key'],
    ];

    $stripe = $this->webformStripeService->getStripeClient($webform);

    try {
      $intent = $stripe->paymentIntents->create($params, $options);
      $response = $intent->confirm();
      $payment_intent_message = $this->t('Payment Created! @intent', [
        '@intent' => Json::encode($response),
      ]);
      $this->logger->info($payment_intent_message);
      return new JsonResponse([
        'paymentIntent' => $response,
        'token' => $csrf_token,
      ], 200);
    }
    catch (\Exception $e) {
      $context = [
        'object' => 'PaymentIntent',
        'method' => 'create',
        'params' => $params,
      ];
      $error_message = $this->t("Payment Failed! \r\n Error: @exception \r\n Context: @context", [
        '@exception' => $e->getMessage(),
        '@context' => Json::encode($context),
      ]);
      $this->logger->info($error_message);
      // Return error message.
      return new JsonResponse([
        'error' => [
          'code' => $e->getHttpStatus(),
          'message' => $e->getMessage(),
          'token' => $csrf_token,
        ],
      ], 200);
    }
  }

  /**
   * Subscription handler.
   *
   * @param mixed[] $data
   *   The payment request data Array.
   * @param string $csrf_token
   *   CSRF token.
   * @param \Drupal\webform\WebformInterface $webform
   *   a Webform instance.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Payment intent from the Subscription obj.
   *
   * @throws \Drupal\wateraid_donation_forms\Exception\PaymentException
   *   If payment failed.
   */
  private function subscriptionHandler(array $data, string $csrf_token, WebformInterface $webform): JsonResponse {

    $donor_details = $this->metadataBuilder($data);
    $payment = $data['donation_details'];

    // Creating Plan.
    $name = $donor_details['first'] . ' ' . $donor_details['last'];
    // Although this is not a Html id, feels like  is a fairly safe
    // way to generate a plan id.
    $plan_id = Html::getId($name) . '_' . time();

    // Pass the UUID as a header for idempotency_key.
    $options = [
      'idempotency_key' => $data['idempotency_key'],
    ];

    $plan_params = [
      'id' => $plan_id,
      'amount' => $payment['amount'] * 100,
      'interval' => 'month',
      'currency' => $payment['currency'],
      'product' => [
        'name' => $name . ' Plan',
      ],
    ];

    $context = [
      'data' => $data,
      'params' => $plan_params,
      'options' => $options,
      'name' => $name,
      'plan_id' => $plan_id,
    ];

    // Log regardless of failure ahead of "/v1/plans" API call.
    $this->logger->info($this->t("Plan creation! \r\n Context: @context", [
      '@context' => Json::encode($context),
    ]));

    $stripe = $this->webformStripeService->getStripeClient($webform);

    try {
      $stripe->plans->create($plan_params, $options);
    }
    catch (\Exception $e) {

      // Log failure of "/v1/plans" API call.
      $this->logger->error($this->t("Plan creation Failed! \r\n Error: @exception \r\n Context: @context", [
        '@exception' => $e->getMessage(),
        '@context' => Json::encode($context),
      ]));

      // Return error message.
      return new JsonResponse([
        'error' => [
          'code' => $e->getHttpStatus(),
          'message' => $e->getMessage(),
          'token' => $csrf_token,
        ],
      ], 200);
    }

    // Creating customer.
    $description = $this->t('Customer for :email', [':email' => $donor_details['customer_email']]);

    $customer_params = [
      'description' => $description,
      'email' => $donor_details['customer_email'],
      'payment_method' => $data['payment_method_id'],
      'metadata' => $donor_details,
      'invoice_settings' => [
        'default_payment_method' => $data['payment_method_id'],
      ],
    ];

    try {
      $result = $stripe->customers->create($customer_params);
      $customer_id = $result['id'];
    }
    catch (\Exception $e) {
      $context = [
        'object' => 'Customer',
        'method' => 'create',
        'params' => $customer_params,
      ];
      $error_message = $this->t("Customer creation Failed! \r\n Error: @exception \r\n Context: @context", [
        '@exception' => $e->getMessage(),
        '@context' => Json::encode($context),
      ]);
      $this->logger->info($error_message);
      // Return error message.
      return new JsonResponse([
        'error' => [
          'code' => $e->getHttpStatus(),
          'message' => $e->getMessage(),
          'token' => $csrf_token,
        ],
      ], 200);
    }

    // Create subscription.
    $subscription_params = [
      'customer' => $customer_id,
      'items' => [
        [
          'plan' => $plan_id,
        ],
      ],
      'default_payment_method' => $data['payment_method_id'],
      'expand' => ['latest_invoice.payment_intent'],
    ];

    try {
      $subscription = $stripe->subscriptions->create($subscription_params);
      /** @var \Stripe\PaymentIntent $intent */
      $intent = $subscription->latest_invoice->payment_intent;
      // Update intent with metadata.
      $intent = $stripe->paymentIntents->update($intent->id, ['metadata' => $donor_details]);
      $intent->subscription_id = $subscription->id;
      $subscription_message = $this->t('Subscription Created!') . Json::encode($subscription);
      $this->logger->info($subscription_message);
      return new JsonResponse([
        'paymentIntent' => $intent,
        'token' => $csrf_token,
      ], 200);
    }
    catch (\Exception $e) {
      $context = [
        'object' => 'Subscription',
        'method' => 'create',
        'params' => $customer_params,
      ];
      $error_message = $this->t("Subscription creation Failed! \r\n Error: @exception \r\n Context: @context", [
        '@exception' => $e->getMessage(),
        '@context' => Json::encode($context),
      ]);
      $this->logger->info($error_message);
      // Return error message.
      return new JsonResponse([
        'error' => [
          'code' => $e->getHttpStatus(),
          'message' => $e->getMessage(),
          'token' => $csrf_token,
        ],
      ], 200);
    }
  }

  /**
   * Get an idempotency key.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A new json response object contains the details of paymentIntent.
   */
  public function getIdempotencyKey(Request $request): JsonResponse {

    if ($request->isXmlHttpRequest() === FALSE) {
      // Error out in this case.
      return new JsonResponse([
        'error' => ['code' => Response::HTTP_FORBIDDEN],
      ], Response::HTTP_FORBIDDEN);
    }

    return new JsonResponse([
      'idempotency_key' => $this->uuidService->generate(),
    ], 200);
  }

}
