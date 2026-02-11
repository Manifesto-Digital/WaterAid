/**
 * @file
 * Javascript behaviors for amount element when v2 styles are used.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Move the impact statistics above the step header.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.wateraidDonationFormsPositionImpactStats = {
    attach: function (context) {
      $(once('webform-impact-stats', '.webform-submission-donation-form-wateraid-v2-form', context)).each(function () {
        const $element = $(this);
        const $statsText = $element.find('.wa-donations--impact-statistics');
        const $stepHeader = $element.find('.step-header');

        $statsText.insertBefore($stepHeader);
      });
    }
  };

  Drupal.behaviors.wateraidDonationFormsDonationReminderAmount = {
    attach: function (context) {
      const webformDonationsParagraph = context.querySelector(
        '.paragraph--type--donation-cta-widget-embed'
      );

      if (!webformDonationsParagraph) {
        // Save one-off donation values to local storage for donation reminder
        // Record value on load if a one-off amount is already set
        if (Drupal.wateraidDonationForms && Drupal.wateraidDonationForms.model.attributes.frequency === 'one_off') {
          sessionStorage.setItem('last_one-off_donation', Drupal.wateraidDonationForms.model.attributes.amount);
        }

        // If there is a donation amount element, add event listeners
        const donationAmountElement = context.querySelector('.wa-element-type-donations-webform-amount');

        if (donationAmountElement) {
          const frequencyButtons = context.querySelectorAll('.form-item-donation-amount-frequency input');
          const amountButtons = context.querySelectorAll('#edit-donation-amount-amount-one-off-amounts-buttons input');
          const otherInput = context.querySelector('.webform-buttons-other-input input');

          frequencyButtons.forEach(button => {
            if (button.value !== 'one_off') {
              sessionStorage.removeItem('last_one-off_donation');
            }

            button.addEventListener('click', () => {
              if (button.value !== 'one_off') {
                sessionStorage.removeItem('last_one-off_donation');
              }
              else {
                amountButtons.forEach(button => {
                  if (button.checked) {
                    sessionStorage.setItem('last_one-off_donation', button.value);
                  }
                });
              }
            });
          });

          amountButtons.forEach(button => {
            if (button.checked) {
              sessionStorage.setItem('last_one-off_donation', button.value);
            }
            button.addEventListener('click', () => {
              if (button.checked) {
                sessionStorage.setItem('last_one-off_donation', button.value);
              }
            });
          });

          if (otherInput) {
            otherInput.addEventListener('change', () => {
              sessionStorage.setItem('last_one-off_donation', otherInput.value);
            });
          }
        }
      }

    }
  }

})(jQuery, Drupal, once);
