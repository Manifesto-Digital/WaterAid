/**
 * @file
 * Scripts that addresses unwanted default webform behaviours and adds improvements such as:
 *
 * Preventing default behaviour on the stripe card number field which breaks form submissions.
 *
 * Back to top button on long forms
 *
 */
 ((Drupal) => {
  'use strict';

  Drupal.behaviors.wateraidDonation = {
    attach(context) {
      /**
       * Prevent default return key behaviour on the card number input field when the submit event
       * is called from the form itself and not due to the submission loop triggered by the
       * submit button.
       * */

      const donationsForm = context.querySelector(
        'form.webform-submission-form.wateraid-donations'
      );

      if (donationsForm) {
        donationsForm.addEventListener('submit', (event) => {
          // Mozilla captures an explicit original target in the event unlike other browsers.
          if (typeof event.explicitOriginalTarget === 'undefined') {
            return;
          } else if (
            event.explicitOriginalTarget.nodeName === 'FORM' &&
            event.isTrusted === false
          ) {
            event.preventDefault();
          }
        });
      }

      /**
       * Legacy code previously implemented in a different way, to redirect user
       * if they click reload. Changed during the front end improvements project.
       * */
      if (typeof PerformanceNavigationTiming !== 'undefined') {
        if (PerformanceNavigationTiming.type === 'reload') {
          window.location = '/';
        }
      }
    }
  };
})(Drupal);
