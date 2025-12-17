/**
 * @file
 * Javascript behaviors for webform Apple Pay button.
 */

(function ($, Drupal, drupalSettings, Backbone, once) {

  'use strict';

  /**
   * Declare view to allow Apple Pay component to interact with the payment model.
   */
  Drupal.wateraidDonationForms.ApplePayView = Backbone.View.extend({
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
      // this.removeBehaviour(event);
    },
    submitFormHandler: function (event) {
      this.createToken(event);
    },
    nextStepHandler: function (event) {
      this.createToken(event);
    },
    createToken: function (event) {},
    addBehaviour: function () {
      Stripe.setPublishableKey(drupalSettings.webformStripe.public_key);
      let serverSidePayment = drupalSettings.wateraidApplePay.serverSidePayment;

      let el = this.el;
      let model = this.model;
      let $errorEl = $('.applepay-errors', this.el);

      if (window.ApplePaySession && window.ApplePaySession.canMakePayments()) {
        $('.apple-pay-button', el).show();
      }
      else {
        $('.apple-pay-button', el).remove();
        $('.wa-donation-method-selection[value="applepay"]').parent().remove();
        $errorEl.html('This browser does not support ApplePay');
      }

      // Handle real-time validation errors from the card Element.
      once('apple-pay-click', '.apple-pay-button', el).forEach(function(element) {
        $(element).click(function (event) {
          // Clear any current error.
          $errorEl.html('');

          let paymentRequest = {
            countryCode: model.get('countryCode'),
            currencyCode: model.get('currency'),
            total: {
              label: 'Payment by Apple Pay',
              amount: model.get('amount')
            },
            customer: drupalSettings.wateraidDonationForms.contact_details
          };

          event.preventDefault();

          let session = Stripe.applePay.buildSession(paymentRequest, function (result, completion) {
            if (serverSidePayment !== 'undefined' && serverSidePayment === true) {
              // Just save the token to continue with standard card style payment.
              model.paymentTokenCreated(result.token.id);
            }
            else {
              $.post(Drupal.url('wateraid-donation-v2/stripe/charge'), {
                paymentRequest: paymentRequest,
                tokenId: result.token.id,
                webformId: drupalSettings.wateraidDonationForms.webform_id,
              }).done(function (result) {
                completion(ApplePaySession.STATUS_SUCCESS);
                model.paymentComplete(result.transactionId);
              }).fail(function (e) {
                completion(ApplePaySession.STATUS_FAILURE);
              });
            }
          }, function (error) {
            $errorEl.html(error.message);
          });
          session.oncancel = function () {};

          session.begin();
        });
      });

      this.mounted = true;
    },
    removeBehaviour: function () {}
  });

})(jQuery, Drupal, drupalSettings, Backbone, once);
