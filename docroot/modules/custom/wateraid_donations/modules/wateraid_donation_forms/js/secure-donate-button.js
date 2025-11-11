/**
 * @file
 * Sticky nav behaviour and custom mobile navigation solution for WaterAid platform.
 */

((Drupal) => {
  'use strict';

  Drupal.behaviors.secureDonateButton = {
    attach(context) {
      console.log('secure-donate-button.js loaded');
      context.querySelectorAll('.input--submit--secure').forEach((element) => {
        // If this is the payment step, the normal submit button may be hidden
        // in favour of the payment provider specific buttons. If the button
        // is hidden in this way, we should ensure our wrapper and secure image
        // are also hidden.
        const formActions = element?.closest('form')?.querySelector('#edit-actions');
        if (formActions) {
          formActions.style.display = 'none';
        }
      });
    }
  };
})(Drupal)
