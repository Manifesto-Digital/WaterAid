/**
 * @file
 * Javascript behaviors for direct debit cancellation messages.
 */

(function ($, Drupal, drupalSettings, Backbone, once) {

  'use strict';

  let step = 0;

  /**
   * Declare view to allow cancellation messages to interact with the donation model.
   */
  Drupal.wateraidDonationForms.cancellationMessagesView = Backbone.View.extend({
    initialize: function () {
      this.listenTo(this.model, 'init', this.render);
      this.listenTo(this.model, 'change:frequency', this.updateMessage);
      this.updateMessage();
      this.render();
    },
    render: function () {
      if (document.querySelector('.step-title__icon')) {
        step = document.querySelector('.step-title__icon').innerText;
        this.model.set('step', step);
      }
    },
    updateMessage: function () {
      const frequency = this.model.get('frequency');
      document.querySelectorAll('.dd-cancellation-message').forEach((message) => {
        message.setAttribute('frequency', frequency);
      });
    }
  });

  /**
   * Attach handlers to the page, check if the message should be displayed.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.wateraidDonationFormsCancellationMessages = {
    attach: function (context) {
      $(once('cancellationMessages', 'form.wateraid-donations')).each(function () {
        let paymentLogosView = new Drupal.wateraidDonationForms.cancellationMessagesView($.extend({el: this, model: Drupal.wateraidDonationForms.model}));
      });

      const ddCancellationMessages = document.querySelectorAll('.dd-cancellation-message');
      const formMessage = document.querySelector('.webform-cancellation-message')
      const sidebarMessage = document.querySelector('.webform-sidebar-cancellation-message');
      const amountElement = document.querySelector('.wa-element-type-donations-webform-amount');

      // Hide the messages after the amount step if one-off is selected
      if (sidebarMessage && sidebarMessage.getAttribute('frequency') === 'one_off' && !amountElement) {
        ddCancellationMessages.forEach(message => {
          message.classList.add('hidden');
        });
      } else if (amountElement) {
        ddCancellationMessages.forEach(message => {
          message.classList.remove('hidden');
        });
      }

      // Hide the mobile/tablet message for later steps
      if (step > 3) {
        formMessage.classList.add('hidden');
      }
    }
  };

})(jQuery, Drupal, drupalSettings, Backbone, once);
