/**
 * @file
 * Javascript behaviors for gift aid element.
 */

(function ($, Drupal, drupalSettings, Backbone, once) {

  'use strict';

  /**
   * Declare view to allow GiftAid component to interact with the donation model.
   */
  Drupal.wateraidDonationForms.GiftAidView = Backbone.View.extend({
    initialize: function () {
      this.listenTo(this.model, 'change:amount', this.render);

      this.render();
    },
    render: function () {
      let amount = '' + this.model.get('amount');
      let giftaid_amount = Number(amount * 1.25).toFixed(2);
      $('.gift-aid-text .amount', this.el).html('£' + amount);
      $('.gift-aid-text .giftaid-amount', this.el).html('£' + giftaid_amount);
    }
  });

    /**
   * Attach handlers to buttons other elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.wateraidDonationFormsGiftAid = {
    attach: function (context) {
      $(once('giftaid', 'div.gift-aid')).each(function () {
        let giftaidView = new Drupal.wateraidDonationForms.GiftAidView($.extend({el: this, model: Drupal.wateraidDonationForms.model}));
      });
    }
  };

})(jQuery, Drupal, drupalSettings, Backbone, once);
