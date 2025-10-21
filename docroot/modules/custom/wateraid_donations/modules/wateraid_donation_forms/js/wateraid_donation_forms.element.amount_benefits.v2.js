/**
 * @file
 * Javascript behaviors for amount benefits element.
 */

((Drupal, once) => {

  'use strict';

  /**
   * Attach handlers to amount benefit.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.wateraidDonationFormsAmountBenefitsV2 = {
    attach(context) {
      once('amountBenefit', '.wa_donations_benefit', context).forEach(
        (amountBenefit) => {
          // Move position of amount benefit in DOM, so it sits underneath
          // each set of amounts (adapted from webform.js - WMS-2523).
          const amountsWrappers = context.querySelectorAll('.wa_donation_amounts_container');
          amountsWrappers.forEach((wrapper) => {
            const amountButtons = wrapper.querySelector('.js-webform-webform-buttons');

            if (amountButtons) {
              const benefit = amountBenefit.cloneNode(true);amountButtons
              amountButtons.after(benefit);
              amountBenefit.remove();
            };
          });
        }
      );
    }
  }
})(Drupal, once);
