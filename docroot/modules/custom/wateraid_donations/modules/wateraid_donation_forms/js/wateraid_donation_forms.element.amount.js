/**
 * @file
 * Javascript behaviors for amount element.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Attach handlers to buttons other elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.wateraidDonationFormsButtonsAmount = {
    attach: function (context) {
      const countDecimals = function (value) {
        if (Math.floor(value) === value) {
          return 0;
        }
        return value.toString().split('.')[1].length || 0;
      }

      $('.webform-buttons-other-input input').focusout(function () {
        // Round to the nearest allowed decimal places for the currency.
        const step = drupalSettings.wateraidDonationForms.currency_step;
        const decimals = countDecimals(step);
        if (this.value) {
          this.value = parseFloat(this.value).toFixed(decimals);
        }
      })
      .keypress(function (e) {
        // Disallow non-numeric characters.
        // @see https://stackoverflow.com/a/42806268
        let allowedChars = '0123456789.';
        function contains(stringValue, charValue) {
          return stringValue.indexOf(charValue) > -1;
        }
        // Masked input by checking for numeric characters and only allow a
        // single ".".
        let invalidKey = e.key.length === 1 && !contains(allowedChars, e.key)
                || e.key === '.' && contains(e.target.value, '.');
        invalidKey && e.preventDefault();
      });

      // Add new indicator
      const $new_frequencies = $(once('new-frequencies', '.wa_donations_frequency.form-radio'));
      $new_frequencies.each(function () {
        if ($(this).hasClass('has-new-frequency')) {
          $(this).next('.option').append('<span class="new-frequency">New</span>');
        }
      })
    }
  };

})(jQuery, Drupal, once);
