(function (Drupal) {
  'use strict';

  Drupal.behaviors.wateraidContentWebform = {
    attach: function (context, settings) {

      const url = window.location.href;
      const checkbox = context.querySelector('.field--name-field-webform-node');

      if (url && checkbox) {
        if (url.includes('webform') || document.body.classList.contains('page-node-type-webform')) {

          // Hide the checkbox on publications.
          checkbox.style.display = 'none';
        }
      }
    }
  };

})(Drupal);
