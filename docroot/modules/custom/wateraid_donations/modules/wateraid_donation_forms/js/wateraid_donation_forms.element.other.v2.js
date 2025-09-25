/**
 * @file
 * JavaScript behaviors for other elements.
 */

(function ($, Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Toggle other input (text) field.
   *
   * @param {boolean} show
   *   TRUE will display the text field. FALSE with hide and clear the text field.
   * @param {object} $element
   *   The input (text) field to be toggled.
   */
  function toggleOther(show, $element) {
    let $input = $element.find('input');
    if (!show) {
      // Clear the custom amount when a preset is selected.
      $input.val('');

      $input.data('webform-value', $input.val());

      // Make the custom amount not required.
      $input.val('').prop('required', false).removeAttr('aria-required').trigger('change');
    }
  }

  /**
   * Attach handlers to buttons other elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.wateraidDonationFormsButtonsOther = {
    attach: function (context) {
      $(once('webform-buttons-other', '.js-webform-buttons-other', context)).each(function () {
        let $element = $(this);

        let $buttons = $element.find('input[type="radio"]');
        let $otherField = $element.find('.js-webform-buttons-other-input');
        let $container = $(this).find('.js-webform-webform-buttons');
        let $label = $element.find('.js-webform-buttons-other-input label');
        let $input = $element.find('.js-webform-buttons-other-input input');

        // Show the other input label
        let currencyCode = drupalSettings.wateraidDonationForms.currency;
        let otherLabelText = Drupal.t('or enter your own amount (@currency_code)', {'@currency_code': currencyCode});
        $label.text(otherLabelText);
        $label.removeClass('visually-hidden');

        // Add pound sign before input
        let currencySign = drupalSettings.wateraidDonationForms.currency_sign;
        $(`<span class=\"webform-buttons-other-input_currency-sign\">${currencySign}</span>`).insertBefore($input);

        // Create set onchange handler.
        $container.on('change', function () {
          toggleOther(($(this).find(':radio:checked').val() === '_other_'), $otherField);
        });

        // Hide the "other" button as it is expanded by default.
        let other_options = $('.webform-buttons-other input[value="_other_"]');
        other_options.each(function () {
          let id = $(this).attr('id');
          // Remove the label.
          $('label[for="' + id + '"]').css('display', 'none');

          // Remove the input.
          $(this).css('display', 'none');
        })

        // Reset the display if necessary
        $otherField.css('display', 'flex');

        // Uncheck pre-selected amounts when 'other' is clicked.
        // Make the field required so an empty amount can't be submitted
        $otherField.click(function () {
          $buttons.filter(':checked').prop('checked', false).trigger('change');
          // Select the 'other' option
          $buttons.filter('[value="_other_"]').prop('checked', true).trigger('change');
          $input.attr('required', 'true');
        });

        // Other input is not required when a radio is selected
        $container.click(function () {
          if ($buttons.filter(':checked')) {
            $input.removeAttr('required');
          }
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings, once);
