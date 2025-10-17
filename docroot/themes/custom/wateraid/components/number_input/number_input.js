(function (Drupal) {
  'use strict';
  Drupal.behaviors.number_input = {
    attach(context) {
      function attachEventListeners(component) {
        const input = component.querySelector('input[type="number"]');
        const btnDecrement = component.querySelector('.number-input__button--decrement');
        const btnIncrement = component.querySelector('.number-input__button--increment');

        // Drupal behaviors can run multiple times on the same content (e.g., AJAX calls).
        // This check ensures we don't attach the same event listeners more than once.
        if (component.dataset.numberInputAttached) {
          return;
        }
        component.dataset.numberInputAttached = 'true';

        /**
         * Checks the current value and disables/enables buttons
         * based on the input's min/max attributes.
         */
        const updateButtonStates = () => {
          const minValue = parseFloat(input.min);
          const maxValue = parseFloat(input.max);
          const currentValue = parseFloat(input.value);

          btnDecrement.disabled = !isNaN(minValue) && currentValue <= minValue;
          btnIncrement.disabled = !isNaN(maxValue) && currentValue >= maxValue;
        };

        // Event listener for the increment button
        btnIncrement.addEventListener('click', () => {
          input.stepUp();
          input.dispatchEvent(new Event('input', {bubbles: true}));
        });

        // Event listener for the decrement button
        btnDecrement.addEventListener('click', () => {
          input.stepDown();
          input.dispatchEvent(new Event('input', {bubbles: true}));
        });

        input.addEventListener('input', updateButtonStates);

        // Initial check on page load
        updateButtonStates();
      }


      context.querySelectorAll('.number-input').forEach((component) => {
        attachEventListeners(component);
      });
    },
  };

})(Drupal);
