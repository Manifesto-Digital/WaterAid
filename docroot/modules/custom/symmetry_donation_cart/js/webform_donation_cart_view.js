/**
 * @file
 * Javascript behaviors for webform DonationCart element.
 */

(function ($, Drupal, drupalSettings, Backbone) {

  'use strict';

  /**
   * Declare view to allow DonationCart component to interact with the payment model.
   */
  Drupal.wateraidDonationForms.DonationCartView = Backbone.View.extend({

    causeCodes: {
      'one_off' : 'OT',
      'recurring' : 'RG'
    },
    paymentMethods: {
      'one_off' : 'CC',
      'recurring' : 'ACH'
    },
    getCauseCode: function (frequency, amount) {
      return this.causeCodes[frequency] + amount;
    },
    getPaymentMethod: function (frequency) {
      return this.paymentMethods[frequency];
    },
    initialize: function () {
      this.listenTo(this.model, 'change:paymentMethod', this.render);
      this.mounted = false;
      this.render();
    },
    render: function () {
      if (!this.mounted) {
        this.addBehaviour();
      }
    },
    addBehaviour: function () {

      var that = this;

      // Handle India donations through 3rd party library.
      $('.webform-button--submit', $(this.el).closest('form')).off().click(function (event) {

        event.preventDefault();

        var cart = new Drupal.donationcart.classes.DonationCart();
        var causeCode = 'GEN';
        var paymentMethod = that.getPaymentMethod(that.model.get('frequency'));
        var units = 1;
        var total = that.model.get('amount').replace(',', '') * units;
        let campaignCode = drupalSettings.symmetryCampaignCode;
        if (campaignCode === 'undefined') {
          campaignCode = 'NoCampaignCode';
        }
        cart.createCartItem(causeCode, 0, units, total, 0);

        try {
          cart.submit(paymentMethod, campaignCode);
        }
        catch (e) {
          console.log(e.message);
        }

        this.mounted = true;
      });

    }
  });

})(jQuery, Drupal, drupalSettings, Backbone);
