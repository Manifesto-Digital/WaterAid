/**
 * @file
 * Javascript behaviors for the payment logos element.
 */

(function ($, Drupal, drupalSettings, Backbone, once) {

  'use strict';

  /**
   * Declare view to allow payment icons to interact with the donation model.
   */
  Drupal.wateraidDonationForms.PayemntLogosView = Backbone.View.extend({
    initialize: function () {
      this.listenTo(this.model, 'init', this.render);
      this.listenTo(this.model, 'change:frequency', this.render);
      this.render();
    },
    render: function () {
      if (this.model.get('frequency') === 'one_off') {
        document.querySelectorAll('[data-toggle-frequency]').forEach((logo) => {
          if (logo.hasAttribute('data-frequency-oneoff')) {
            logo.classList.remove('hidden');
          }
          else {
            logo.classList.add('hidden');
          }
        });
      }

      if (this.model.get('frequency') === 'recurring') {
        document.querySelectorAll('[data-toggle-frequency]').forEach((logo) => {
          if (logo.hasAttribute('data-frequency-recurring')) {
            logo.classList.remove('hidden');
          }
          else {
            logo.classList.add('hidden');
          }
        });
      }
    }
  });

  /**
   * Attach handlers to the page.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.wateraidDonationFormsPaymentLogos = {
    attach: function (context) {
      $(once('paymentlogos', 'body')).each(function () {
        let paymentLogosView = new Drupal.wateraidDonationForms.PayemntLogosView($.extend({el: this, model: Drupal.wateraidDonationForms.model}));
      });
    }
  };

})(jQuery, Drupal, drupalSettings, Backbone, once);
