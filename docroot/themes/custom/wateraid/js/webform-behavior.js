(function (Drupal, drupalSettings) {
  'use strict';


  const replaceSubmit = (findSubmit) => {
    const newElement = document.createElement('button');
    newElement.textContent = findSubmit.value;
    findSubmit.parentNode.replaceChild(newElement, findSubmit);

    newElement.addEventListener('click', (e) => {
      e.preventDefault();
      window.location.href = window.location.href.split('?')[0];
    });
  }

  Drupal.behaviors.wateraidWebformBehavior = {
    attach: function (context, settings) {
      let findSubmit = document.querySelector('.field--name-webform form fieldset:first-of-type input[type="submit"]:not([data-drupal-selector="edit-contact-address-manual"])');
      if (!findSubmit) {
        findSubmit = context.querySelector('.webform-submission-form form [data-edit-step="step_1"]');
      }

      if (findSubmit) {
        replaceSubmit(findSubmit);
      }
    }
  };

  Drupal.behaviors.paymentButtonHoverEffect = {
    attach: function (context, settings) {
      // The payment button has to be input for the form to work.
      // It also has to contain a padlock icon, so we're wrapping it in a label styled as a button.
      // Here we add hover effects to the input button by proxying the focus state from the label.

      const paymentButton = context.querySelector('.field--name-webform input.payment-button');
      const labelWrapper = context.querySelector('.field--name-webform label.button__input-button-wrapper');

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

})(Drupal, drupalSettings);
