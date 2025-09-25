/**
 * @file
 * JavaScript behaviors for payment elements.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Attach handlers to payment buttons.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.wateraidDonationFormsPaymentElement = {
    attach: function (context) {
      $(once('donations-webform-payment', $(context).find('.donations-webform-payment--wrapper'))).each(function () {
        const $paymentElemants = $(this);

        let $startDateOptions = $paymentElemants.find('.start-dates');
        let $startDateLabels = $startDateOptions.find('.option');
        const $radioMarks = $paymentElemants.find('.webform-radio');

        // When an option is clicked, find it's lable and cheang the text colour
        $radioMarks.on('click', function () {
          // Reset class
          $startDateLabels.each(function () {
            $(this).removeClass('checked');
          });
          // Mark the label for the checked radio
          $(this).next().addClass('checked');
        });
      });
    }
  };

})(jQuery, Drupal, once);
