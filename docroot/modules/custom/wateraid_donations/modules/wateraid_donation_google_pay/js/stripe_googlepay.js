/**
 * @file
 * Javascript behaviors for the Google Pay integration.
 */

(function ($, Drupal, drupalSettings, Backbone) {

  'use strict';

  /**
   * View to allow Google Pay to interact with the payment model.
   */
  Drupal.wateraidDonationForms.GooglePayView = Backbone.View.extend({
    initialize: function () {
      this.listenTo(this.model, 'change:paymentMethod', this.render);
      this.listenTo(this.model, 'backStep', this.backStepHandler);
      this.listenTo(this.model, 'nextStep', this.nextStepHandler);
      this.listenTo(this.model, 'submitForm', this.submitFormHandler);
      this.listenTo(this.model, 'refresh', this.render);
      this.mounted = false;
      this.addBehaviour();
    },

    render: function () {
      if (this.model.isCurrentPaymentMethod(this)) {
        this.removeBehaviour();
        $(this.el).closest('form').find('.form-actions input[type="submit"]').hide();
      }
      else {
        this.addBehaviour();
      }
    },
    backStepHandler: function (event) {
      this.removeBehaviour(event);
    },
    submitFormHandler: function (event) {
      this.createToken(event);
    },
    nextStepHandler: function (event) {
      this.createToken(event);
    },
    createToken: function (event) {},
    addBehaviour: function () {
      this.stripe = Stripe(drupalSettings.webformStripe.public_key, {
        apiVersion: '2020-08-27',
      });

      let el = this.el;
      let model = this.model;
      let $errorEl = $('.googlepay-errors', this.el);

      // Get the donation amount.
      let amount = model.get('amount');

      // Google Pay accepts the amount in the smallest denomination (pence).
      amount = amount * 100;

      let currency = model.get('currency').toLowerCase();
      let paymentRequest

      try {
        paymentRequest = this.stripe.paymentRequest({
          country: model.get('countryCode'),
          currency: currency,
          total: {
            label: 'Payment by Google Pay',
            amount: amount
          },
          customer: drupalSettings.wateraidDonationForms.contact_details
        });
      }
      catch (e) {
        console.warn("Unable to initialise GooglePay", e)
        return true
      }


      const elements = this.stripe.elements();
      const prButton = elements.create('paymentRequestButton', {
        paymentRequest: paymentRequest,
      })

      paymentRequest.canMakePayment().then((result) => {
        if (!(typeof result === 'object' && result !== null && result.googlePay === true)) {
          // Hide the button.
          $('.google-pay-button', el).hide();
          $('.wa-donation-method-selection[value="googlepay"]').parent().addClass('visually-hidden');
          $errorEl.html('This browser does not support GooglePay');
        } else {
          // Mount the button.
          prButton.mount('.google-pay-button');
          $('.wa-donation-method-selection[value="googlepay"]').parent().removeClass('visually-hidden');
          $('.google-pay-button', el).show();
        }
      });

      paymentRequest.on('paymentmethod', async(result) => {
        let scatoken = document.getElementById('token').value;
        // Remove any url from token.
        scatoken = scatoken.replace(/(?:https?|ftp):\/\/[\n\S]+/g, '');

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

        $.ajax(Drupal.url('wateraid-donation-v2/stripe/sca/payment_intent' + '?token=' + scatoken), {
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
              // Wrap result in a paymentIntent property.
              document.getElementById('payment-response-result').value = JSON.stringify({
                "paymentIntent" : paymentIntent
              }, null, 2);

              if (response.error) {
                // Payment intent failed.
                result.complete('fail');
              }
              else if (paymentIntent.status === 'succeeded') {
                // Payment was successful - submit the Webform.
                result.complete('success');
                $('.webform-button--submit').click();
              }
              else {
                // Unknown payment intent status.
                result.complete('fail');
              }
            }
            else {
              // Payment intent returned a non-200 status code.
              result.complete('fail');
            }
          }
        });
      });

      this.mounted = true;
    },
    removeBehaviour: function () {}
  });
})(jQuery, Drupal, drupalSettings, Backbone);
