/**
 * @file
 * Javascript behaviors for webform PayPal elements.
 */

(function ($, Drupal, drupalSettings, Backbone) {

  'use strict';

  /**
   * Declare view to allow PayPal component to interact with the payment model.
   */
  Drupal.wateraidDonationForms.PayPalView = Backbone.View.extend({
    /**
     * {@inheritdoc}
     */
    initialize: function () {
      this.listenTo(this.model, 'change:paymentMethod', this.render);

      this.mounted = false;

      this.render();
    },

    render: function () {
      if (!this.mounted) {
        this.addBehaviour();
      }
      if (this.model.isCurrentPaymentMethod(this)) {
        $(this.el).closest('form').find('.form-actions input[type="submit"]:not(#edit-actions-wizard-prev)').hide();
      }
    },

    addBehaviour: function () {
      let mode = drupalSettings.webformPayPalExpressElements.mode;
      let client = drupalSettings.webformPayPalExpressElements.client;
      let model = this.model;
      let el = this.el;
      let $errorEl = $('.paypal-express-errors', el);

      if (!client || !client[mode]) {
        return true
      }

      $('.paypal-express-container', this.el).each(function () {
        paypal.Button.render({
          style: {
            size: 'medium'
          },
          env: mode,

          client: client,

          commit: true, // Show a 'Pay Now' button

          payment: function (data, actions) {
            // Clear any error
            $errorEl.html('');

            $errorEl.html(Drupal.t('Your payment is being processed: please wait.'));

            // Set up the payment here, when the buyer clicks on the button
            let amount = model.get('amount');
            let currency = model.get('currency');
            return actions.payment.create({
              transactions: [
                {
                  amount: { total: amount, currency: currency }
                }
              ]
            });
          },

          onAuthorize: function (data, actions) {
            // Execute the payment here, when the buyer approves the transaction
            return actions.payment.execute().then(function (payment) {
              // The payment is complete!
              model.paymentComplete(JSON.stringify(payment));
              $errorEl.html('');
            });
          },

          onError: function (err) {
            $errorEl.html('');
            // Handle real-time validation errors from PayPal.
            $errorEl.html(Drupal.t('There was a problem with your PayPal payment. Please try again.'));
          }
        }, this.id);
      });

      this.mounted = true;

    },
  });

})(jQuery, Drupal, drupalSettings, Backbone);
