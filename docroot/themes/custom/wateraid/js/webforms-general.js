(function (Drupal, drupalSettings) {
  'use strict';


  Drupal.behaviors.donateSecurelyFocus = {
    attach: function (context, settings) {
      // The payment button has to be input for the form to work.
      // It also has to contain a padlock icon, so we're wrapping it in a label styled as a button.
      // Here we add focus effects to the label wrapper by proxying the focus state from the input.

      const paymentButton = context.querySelector('form.webform-category-donation input[data-component-id="wateraid:button"]');
      const labelWrapper = paymentButton?.closest('label.button__input-button-wrapper');

      if (paymentButton && labelWrapper) {
        // Add focus class to input when label is focused
        paymentButton.addEventListener('focus', function() {
          labelWrapper.classList.add('focus');
        });

        // Remove focus class from input when label loses focus
        paymentButton.addEventListener('blur', function() {
          labelWrapper.classList.remove('focus');
        });
      }
    }
  };

  Drupal.behaviors.increaseImpactBehaviour = {
    attach: function (context, settings) {
      setTimeout(() => {
        const impactTags = document.querySelectorAll(".wateraid-donation-amount-upsell-tooltip");
        impactTags.forEach((impactTag) => {
          impactTag.classList.add("is-hidden");
        });
      }, 10000);
    }
  };

  Drupal.behaviors.zakatDonateHover = {
    attach: function (context, settings) {
      const buttonField = context.querySelector(".form-item-donate-button");
      if (buttonField) {
        const hoverSpan = context.createElement("span");
        hoverSpan.classList.add("button__hover");
        buttonField.appendChild(hoverSpan);
      }
    },
  };

})(Drupal, drupalSettings);
