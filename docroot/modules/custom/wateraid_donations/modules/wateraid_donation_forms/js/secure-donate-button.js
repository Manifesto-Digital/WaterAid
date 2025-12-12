/**
 * @file
 * Sticky nav behaviour and custom mobile navigation solution for WaterAid platform.
 */

((Drupal) => {
  'use strict';

  Drupal.behaviors.secureDonateButton = {
    attach(context) {
      context.querySelectorAll('.input--submit--secure').forEach((element) => {
        // If this is the payment step, the normal submit button may be hidden
        // in favour of the payment provider specific buttons. If the button
        // is hidden in this way, we should ensure our wrapper and secure image
        // are also hidden.
        const form = element?.closest('form');
        if (form) {
          if( form.querySelector('input[name="payment[payment_frequency]"]')?.value !== 'recurring') {
            const formActions = form?.querySelector('[data-drupal-selector="edit-actions"]');
            if (formActions) {

              // If the actions element has our secure submit inside it, we
              // don't want to hide anything because then you can't submit the
              // step. But otherwise we can.
              if (!formActions.contains(element)) {
                formActions.style.display = 'none';
              }
            }
          }
        }
      });
    }
  };
})(Drupal)
