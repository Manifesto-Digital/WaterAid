/**
 * @file
 * Javascript behaviors for webform stripe elements.
 */

(function ($, Drupal, drupalSettings, Backbone) {

  'use strict';

  /**
   * Declare view to allow stripe component to interact with the payment model.
   */
  Drupal.wateraidDonationForms.StripeView = Backbone.View.extend({
    initialize: function (options) {
      this.listenTo(this.model, 'change:paymentMethod', this.render);
      this.listenTo(this.model, 'backStep', this.backStepHandler);
      this.listenTo(this.model, 'nextStep', this.nextStepHandler);
      this.listenTo(this.model, 'submitForm', this.submitFormHandler);
      this.listenTo(this.model, 'refresh', this.render);
      this.mounted = false;
    },
    render: function () {
      if (this.model.isCurrentPaymentMethod(this)) {
        this.addBehaviour();
        $(this.el).closest('form').find('.form-actions input[type="submit"]:not(#edit-actions-wizard-prev)').hide();
      }
      else {
        this.removeBehaviour();
      }
    },
    backStepHandler: function (event) {
      this.removeErrormessage();
      this.removeBehaviour(event);
    },
    submitFormHandler: function (event) {
      this.removeErrormessage();

      // Ensure we're only altering the submit event when dealing with Stripe payments.
      if ($.inArray(this.model.attributes.paymentMethod, ["stripe", "stripe_subscription", "stripe_fixed_period"]) !== -1) {

        if (document.getElementById('payment-response-result').value === "") {
          event.preventDefault();
          // Check for valid Stripe card element.
          if ($('.StripeElement--complete').length === 0) {
            return false;
          }

          // Do not proceed if createPaymentMethod is already initiated.
          if (this.model.getDisableCreatePaymentMethod() === true) {
            return false;
          }

          // Disable init of payment method and run through.
          this.model.setDisableCreatePaymentMethod(true);
          this.createPaymentMethod(event);
        }
      }
    },
    nextStepHandler: function (event) {
      this.removeErrormessage();
      this.addBehaviour(event);
    },
    removeErrormessage: function () {
      // Removing the duplicate messages.
      if (document.querySelector('div[data-drupal-message-id="stripe-authentication"]')) {
        let existingMessage = document.querySelector('div[data-drupal-message-id="stripe-authentication"]');
        existingMessage.parentNode.removeChild(existingMessage);
      }
    },
    showErrorMessage: function (cardMessage, errorMessage) {
      errorMessage = errorMessage || Drupal.t('We were unable to take your payment. Please check your details and try again.');
      const messages = new Drupal.Message();
      messages.add(errorMessage, {type: 'error', id:'stripe-authentication'});

      // If single page webform
      let stepMessages = document.querySelector('.webform_step_status_messages')
      if (stepMessages) {
        // Move the message to the step messages
        let errorMessageElement = document.querySelector('div .messages--error');
        let clonedMessage = errorMessageElement.cloneNode(true);
        errorMessageElement.remove();
        clonedMessage.classList.add('control-width', 'status-messages');
        stepMessages.append(clonedMessage);
      } else {
        // Otherwise, display as normal
        let errorMessageElement = document.querySelector('div .messages--error');
        errorMessageElement.classList.add('control-width', 'status-messages');
        document.getElementsByClassName('messages__wrapper')[0].scrollIntoView(true);
      }

      if (cardMessage) {
        cardMessage.textContent = '';
      }

      // Clear card element & Stripe variables.
      this.removeBehaviour();
      this.addBehaviour();
    },
    getIdempotencyKey: function () {
      $.ajax(Drupal.url('wateraid-donation-stripe-v2/sca/idempotency-key'), {
        method: 'POST',
        dataType: 'json',
        headers: {
          'Content-Type': 'application/json'
        }
      }).then(function (response) {
        document.getElementById('idempotency-key').value = response.idempotency_key;
      });
    },
    createPaymentMethod: function (event) {
      /**
       *  Remove the card number field and submit button from view
       *  upon a user submitting them to prevent multiple submissions
       */
      let cardNumberField = document.querySelector('.stripe-card-element');
      let donationSubmitButton = document.querySelector('.webform-button--submit.button--primary');
      let donationSubmitLoader = document.querySelector('.webform-button--loading');
      let donationTypeSelector = document.querySelector('.wa-donation-method-selection');
      let webformBackButton = document.querySelector('.webform-button--previous');

      function hideInputFields(hide) {
        if (hide) {
          if (cardNumberField) { cardNumberField.style.visibility = "hidden";}
          if (donationSubmitButton) { donationSubmitButton.style.visibility = "hidden";}
          if (donationSubmitLoader) { donationSubmitLoader.style.display = "block";}
          if (webformBackButton) { webformBackButton.style.visibility = "hidden";}
          if (donationTypeSelector) { donationTypeSelector.style.visibility = "hidden";}
          return;
        }
        if (cardNumberField) {cardNumberField.style.visibility = "visible";}
        if (donationSubmitButton) { donationSubmitButton.style.visibility = "visible";}
        if (donationSubmitLoader) { donationSubmitLoader.style.display = "none";}
        if (webformBackButton) { webformBackButton.style.visibility = "visible";}
        if (donationTypeSelector) { donationTypeSelector.style.visibility = "visible";}
      }

      hideInputFields(true);

      if (this.mounted) {
        let cardMessage = document.getElementsByClassName('stripe-card-errors')[0];
        let stripeTokenId = '#stripe-token';
        if (Drupal.wateraidDonationForms.model.attributes.paymentMethod === 'stripe_subscription') {
          cardMessage = document.getElementById('stripe-card-errors--2');
          stripeTokenId = '#stripe-token--2';
        }

        // If we already have a token allow the form to be submitted.
        if (typeof $(stripeTokenId, this.el).val() !== "undefined") {
          if ($(stripeTokenId, this.el).val().length) {
            this.model.setDisableCreatePaymentMethod(false);
            return true;
          }
        }

        let $errorEl = $('.stripe-card-errors', this.el);
        $errorEl.html('');
        let el = this.el;
        let originalEvent = event;

        // Assign 'this' to a variable to maintain context within the scope of the createPaymentMethod() method.
        let that = this;

        this.stripe.createPaymentMethod('card', this.stripe_el).then(function (result) {
          if (result.error) {
            // Inform the user if there was an error.
            $errorEl.html(result.error.message);
            hideInputFields(false);
            that.getIdempotencyKey();
            that.model.setDisableCreatePaymentMethod(false);
            $('.form-actions .webform-button--submit').removeClass('sca-is-disabled');
          }
          else {
            $(stripeTokenId, el).val(result.paymentMethod.id);
            // Stripe SCA 3d secure authentication handling from here.
            let paymentMethodId = result.paymentMethod.id;
            let donor_meta_details = {};
            if (drupalSettings.wateraidDonationForms.contact_details) {
              donor_meta_details = drupalSettings.wateraidDonationForms.contact_details;
            }
            else if (Object.entries(Drupal.wateraidDonationForms.model.attributes.contactAddress).length > 0) {
              donor_meta_details = Drupal.wateraidDonationForms.model.attributes.contactAddress;
            }
            else if (Drupal.wateraidDonationForms.model.attributes.contactAddressSweden) {
              donor_meta_details = Drupal.wateraidDonationForms.model.attributes.contactAddressSweden;
            }
            if (!Drupal.wateraidDonationForms.model.attributes.amount) {
              cardMessage.textContent = Drupal.t("There is no amount attached with this transaction.");
              that.model.setDisableCreatePaymentMethod(false);
              return false;
            }

            if (cardMessage) {
              cardMessage.textContent = Drupal.t("Please do not refresh the page and wait while we are processing your payment. This can take a few minutes....");
            }
            let scatoken = document.getElementById('token').value;
            // Remove any url from token.
            scatoken = scatoken.replace(/(?:https?|ftp):\/\/[\n\S]+/g, '');
            $.ajax(Drupal.url('wateraid-donation-stripe-v2/sca/payment_intent' + '?token=' + scatoken), {
              method: 'POST',
              dataType: 'json',
              headers: {
                'Content-Type': 'application/json'
              },
              data: JSON.stringify({
                payment_method_id: result.paymentMethod.id,
                donation_details: Drupal.wateraidDonationForms.model,
                donor_meta_details: donor_meta_details,
                idempotency_key: document.getElementById('idempotency-key').value,
                webform_id: drupalSettings.wateraidDonationForms.webform_id,
              }),
              complete: function (e) {
                let response = e.responseJSON;
                if (e.status === 200) {
                  // Our response will contain a payment intent.
                  let paymentIntent = response.paymentIntent;
                  let subscriptionSchedule = response.subscriptionSchedule;
                  // Wrap result in a paymentIntent/subscriptionSchedule property.
                  document.getElementById('payment-response-result').value = JSON.stringify({
                    "paymentIntent" : paymentIntent,
                    "subscriptionSchedule": subscriptionSchedule,

                  }, null, 2);

                  if (response.error) {
                    // Show the card number field and submit button if the payment intent response has an error.
                    hideInputFields(false);
                    that.showErrorMessage(cardMessage);
                    that.getIdempotencyKey();
                    that.model.setDisableCreatePaymentMethod(false);
                    $('.form-actions .webform-button--submit').removeClass('sca-is-disabled');
                    document.getElementById('token').value = response.error.token;
                  }
                  else if (paymentIntent && paymentIntent.status === 'succeeded') {
                    // No need to perform additional SCA auth on the payment intent.
                    $('.webform-button--submit').click();
                  }
                  else if (subscriptionSchedule && subscriptionSchedule.status === 'active') {
                    // No need to perform additional SCA auth on the payment intent.
                    $('.webform-button--submit').click();
                  }
                  else {
                    that.stripe.handleCardPayment(
                      paymentIntent.client_secret, {payment_method: paymentMethodId}
                    ).then(function (result) {
                      // The paymentIntent here is a property of the Stripe result obj.
                      document.getElementById('payment-response-result').value = JSON.stringify(result, null, 2);

                      if (result.error) {
                        // Show the card number field and submit button if the user manually fails the SCA.
                        hideInputFields(false);
                        that.showErrorMessage(cardMessage);
                        that.getIdempotencyKey();
                        that.model.setDisableCreatePaymentMethod(false);
                        $('.form-actions .webform-button--submit').removeClass('sca-is-disabled');
                        document.getElementById('token').value = response.token;
                      }
                      else {
                        $('.webform-button--submit').click();
                      }
                    });
                  }
                }
                else {
                  // Show the card number field and submit button if the POST response isn't 200.
                  hideInputFields(false);
                  that.showErrorMessage(cardMessage);
                  that.getIdempotencyKey();
                  that.model.setDisableCreatePaymentMethod(false);
                  $('.form-actions .webform-button--submit').removeClass('sca-is-disabled');
                  document.getElementById('token').value = response.error.token;
                }
              }
            });

            // Re-fire the original event.
            $(originalEvent.target).trigger(originalEvent.type);
          }
        });
        return true;
      }
      this.model.setDisableCreatePaymentMethod(false);
    },
    addBehaviour: function () {
      if (this.model.isCurrentPaymentMethod(this)) {
        let stripeOptions = {};
        if (drupalSettings.webformStripe.api_version !== null) {
          stripeOptions.apiVersion = drupalSettings.webformStripe.api_version;
        }
        this.stripe = new Stripe(drupalSettings.webformStripe.public_key, stripeOptions);
        let elements = this.stripe.elements();
        // let style = drupalSettings.webformStripeElements.style;

        let $button_element = $('.stripe-card-element', this.el);
        this.stripe_el = elements.create('card', { hidePostalCode: true});
        this.stripe_el.mount($button_element[0]);

        let $errorEl = $('.stripe-card-errors', this.el);
        $errorEl.html('');

        this.mounted = true;
      }
      else {
        this.mounted = false;
      }
    },
    removeBehaviour: function () {
      if (this.mounted && this.stripe_el !== 'undefined') {
        this.stripe_el.unmount();
        if (Drupal.wateraidDonationForms.model.attributes.paymentMethod === 'stripe_subscription') {
          $('#stripe-token--2').val('');
        }
        else {
          $('#stripe-token').val('');
        }
        $('#payment-response-result').val('');
        this.mounted = false;
      }
    }
  });

})(jQuery, Drupal, drupalSettings, Backbone);
