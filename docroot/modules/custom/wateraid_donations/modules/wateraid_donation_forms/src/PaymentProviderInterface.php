<?php

namespace Drupal\wateraid_donation_forms;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\WebformInterface;

/**
 * PaymentProvider Interface.
 *
 * @package Drupal\wateraid_donation_forms
 */
interface PaymentProviderInterface extends ContainerFactoryPluginInterface {

  /**
   * Return the UI label.
   *
   * @return string
   *   The label.
   */
  public function getUiLabel(): string;

  /**
   * Return the Export label.
   *
   * This is used for the export to CRM. Falls back to UI label if not
   * specified.
   *
   * @return string
   *   The label.
   */
  public function getExportLabel(): string;

  /**
   * Return the JS view.
   *
   * @return string
   *   The js view.
   */
  public function getJsView(): string;

  /**
   * Return the payment type.
   *
   * @return string
   *   The payment type id.
   */
  public function getPaymentType(): string;

  /**
   * Return the payment frequency.
   *
   * @return string
   *   The payment frequency.
   */
  public function getPaymentFrequency(): string;

  /**
   * Get a webform element.
   *
   * @return \Drupal\Core\Render\Element\FormElement|null
   *   A form element.
   */
  public function getElement(): ?FormElement;

  /**
   * Process a webform composite element.
   *
   * Use to add additional elements required by the payment provider.
   *
   * @param mixed[] $element
   *   Element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param mixed[] $complete_form
   *   Complete form.
   */
  public function processWebformComposite(array &$element, FormStateInterface $form_state, array &$complete_form): void;

  /**
   * Process a payment.
   *
   * @param mixed[] $payment
   *   Payment details.
   * @param \Drupal\webform\WebformInterface|null $webform
   *   a Webform instance or NULL.
   *
   * @return mixed
   *   Result.
   *
   * @throws \Drupal\wateraid_donation_forms\Exception\PaymentException
   *   Throws a PaymentException on failure.
   * @throws \Drupal\wateraid_donation_forms\Exception\UserFacingPaymentException
   *   An exception message which can be shown to the user.
   * @throws \Exception
   *   On a missing WebformInterface if needed.
   */
  public function processPayment(array $payment, ?WebformInterface $webform = NULL): mixed;

  /**
   * Get the transaction id from a result.
   *
   * @param mixed $result
   *   The result returned by processPayment().
   *
   * @return string
   *   A transaction id.
   */
  public function getTransactionId(mixed $result): string;

  /**
   * Get payment details.
   *
   * @param mixed[] $payment
   *   Payment details.
   * @param mixed[]|false $result
   *   Payment result details.
   * @param \Drupal\webform\WebformInterface|null $webform
   *   a Webform instance or NULL.
   *
   * @return mixed[]
   *   Keyed array of payment data.
   *
   * @throws \Exception
   */
  public function getPaymentData(array $payment, array|false $result, ?WebformInterface $webform = NULL): array;

  /**
   * Gets the upper limit of payments.
   *
   * @return mixed
   *   Integer of upper limit.
   */
  public function getUpperLimit(): mixed;

  /**
   * Gets the "requires customer fields" identifier.
   *
   * @return mixed
   *   TRUE or FALSE.
   */
  public function getRequiresCustomerFields(): mixed;

}
