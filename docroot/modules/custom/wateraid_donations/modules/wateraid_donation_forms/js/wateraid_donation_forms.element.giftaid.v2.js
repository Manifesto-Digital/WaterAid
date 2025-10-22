/**
 * @file
 * Javascript behaviors for gift aid element in donations V2.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.GiftAidV2Style = {
    attach: function (context) {
      // Move the giftaid image to next to the header
      const $giftAidImage = $('.gift-aid-logo').addClass('step-header__elements');
      if ($giftAidImage) {
        const $header = $('.webform-style-v2 h2:first').addClass('step-header__elements');

        $($giftAidImage).insertAfter($header);
        $('.step-header__elements').wrapAll('<div class="step-header" />')
      }

    }
  };

})(jQuery, Drupal);
