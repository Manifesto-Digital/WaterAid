/**
 * @file
 * Site search facets.
 */
((Drupal, drupalSettings) => {
  'use strict';

  Drupal.behaviors.siteAccessibility = {
    attach(context, settings) {
      // Removed aria-required from wa-composite-email-hash
      var elements = document.getElementsByClassName('wa-composite-email-hash');
      for (var i = 0; i < elements.length; i++) {
        elements[i].removeAttribute('aria-required');
      }

      
    }
  };
})(Drupal, drupalSettings);
