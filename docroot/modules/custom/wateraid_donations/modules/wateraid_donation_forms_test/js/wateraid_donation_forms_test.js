/**
 * @file
 * Javascript behaviors for webform donation test elements.
 */

(function ($, Drupal, drupalSettings, Backbone, once) {

  'use strict';

  /**
   * Declare view to allow PayPal component to interact with the payment model.
   */
  Drupal.wateraidDonationForms.OneOffTestView = Backbone.View.extend({
    /**
     * {@inheritdoc}
     */
    initialize: function () {
      this.listenTo(this.model, 'change:paymentMethod', this.render);
      this.listenTo(this.model, 'backStep', this.backStepHandler);
      this.listenTo(this.model, 'nextStep', this.nextStepHandler);
      this.listenTo(this.model, 'submitForm', this.submitFormHandler);

      this.mounted = false;

      this.render();
    },

    render: function () {
      let paymentMethod = this.model.get('paymentMethod');

      if (paymentMethod != 'test_one_off') {
        this.removeBehaviour();
      }
      else {
        this.addBehaviour();
      }
    },

    backStepHandler: function (event) {
      this.removeBehaviour(event);
    },

    submitFormHandler: function (event) {
    },

    nextStepHandler: function (event) {
    },

    addBehaviour: function () {

      let model = this.model;
      let el = this.el;
      $(once('payment-behaviour', '.client-response', this.el)).on('ifClicked', function () {
        let random_token = Math.floor((Math.random()) * 100000000);
        let payment = {token: 'test_token_' + random_token};
        model.paymentComplete(JSON.stringify(payment));
      });

      this.mounted = true;
    },

    removeBehaviour: function () {
    }
  });

})(jQuery, Drupal, drupalSettings, Backbone, once);
