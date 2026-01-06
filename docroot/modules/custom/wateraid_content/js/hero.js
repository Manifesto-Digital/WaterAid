(function (Drupal) {
  'use strict';

  Drupal.behaviors.wateraidContentHero = {
    attach: function (context, settings) {

      const url = window.location.href;
      const checkbox = context.querySelector('.js-form-item-field-show-read-time-value');

      if (url && checkbox) {
        if (url.includes('publication')) {

          // Hide the checkbox on publications.
          checkbox.style.display = 'none';
        }
      }
    }
  };

})(Drupal);
