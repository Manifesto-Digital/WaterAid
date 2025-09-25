<?php

namespace Drupal\wateraid_donation_stripe\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\wateraid_donation_forms\Exception\PaymentException;
use Drupal\wateraid_donation_forms\PaymentProviderPluginManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows manipulation of the response object when performing a redirect.
 */
class StripeController extends ControllerBase {

  /**
   * The state service.
   */
  protected PaymentProviderPluginManager $paymentPluginManager;

  /**
   * A logger instance.
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a StripeController object.
   *
   * @param \Drupal\wateraid_donation_forms\PaymentProviderPluginManager $payment
   *   The state service.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger instance.
   */
  public function __construct(PaymentProviderPluginManager $payment, LoggerInterface $logger) {
    $this->paymentPluginManager = $payment;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('plugin.manager.payment_provider'),
      $container->get('logger.channel.wateraid_donation_stripe')
    );
  }

  /**
   * Ajax callback to create a Stripe Apple Pay charge.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Result of charge.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\wateraid_donation_forms\Exception\UserFacingPaymentException
   */
  public function chargeApplePay(Request $request): AjaxResponse {

    if ($request->isXmlHttpRequest() === FALSE) {
      return new AjaxResponse([], Response::HTTP_FORBIDDEN);
    }

    try {
      $payment_request = $request->get('paymentRequest');
      $token_id = $request->get('tokenId');
      $webform_id = $request->get('webformId');
      $customer_details = Json::decode($payment_request['customer']);

      /** @var \Drupal\wateraid_donation_forms\PaymentProviderInterface $apple_pay_payment_provider */
      $apple_pay_payment_provider = $this->paymentPluginManager->createInstance('applepay');

      $payment = [
        'payment_token' => $token_id,
        'description' => $payment_request['total']['label'],
        'amount' => $payment_request['total']['amount'],
        'currency' => $payment_request['currencyCode'],
        'customer_details' => $customer_details,
      ];

      /** @var \Drupal\webform\WebformInterface $webform */
      $webform = $this->entityTypeManager()->getStorage('webform')->load($webform_id);
      if (!$webform) {
        $this->logger->error('Invalid Webform Id ":webform_id" passed', [':webform_id' => $webform_id]);
        throw new PaymentException('Invalid Payment Request');
      }

      $result = $apple_pay_payment_provider->processPayment($payment, $webform);

      $transaction_id = $apple_pay_payment_provider->getTransactionId($result);
    }
    catch (PaymentException $e) {
      return new AjaxResponse([], Response::HTTP_NOT_ACCEPTABLE);
    }

    return new AjaxResponse(['transactionId' => $transaction_id]);
  }

}
