/**
 * @file
 * Javascript behaviors for wateraid forms.
 */

(function ($, Drupal) {

  'use strict';

  function waterAidCheckValid(e) {
    if ($(e).val().length === 0) {
      $(e).removeClass('valid');
    }
  }

  /**
   * Add behaviours for wateraid webform elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.wateraidForms = {
    attach: function (context) {
      $('input')
        .blur(function () {
          waterAidCheckValid(this);
        })
        .on('keyup', function () {
          waterAidCheckValid(this);
        });
    }
  };

})(jQuery, Drupal);
