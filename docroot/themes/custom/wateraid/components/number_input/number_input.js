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
         * Custom stepUp implementation that respects the step attribute
         * and handles min/max constraints.
         */
        const stepUp = () => {
          const currentValue = parseFloat(input.value) || 0;
          const maxValue = parseFloat(input.max);
          let newValue = currentValue + 1;

          // Respect max value constraint
          if (!isNaN(maxValue) && newValue > maxValue) {
            newValue = maxValue;
          }

          input.value = newValue.toFixed(2);
          input.dispatchEvent(new Event('input', {bubbles: true}));
          input.dispatchEvent(new Event('change', {bubbles: true}));
        };

        /**
         * Custom stepDown implementation that respects the step attribute
         * and handles min/max constraints.
         */
        const stepDown = () => {
          const currentValue = parseFloat(input.value) || 0;
          const minValue = parseFloat(input.min);
          let newValue = currentValue - 1;

          // Respect min value constraint
          if (!isNaN(minValue) && newValue < minValue) {
            newValue = minValue;
          }

          input.value = newValue.toFixed(2);
          input.dispatchEvent(new Event('input', {bubbles: true}));
          input.dispatchEvent(new Event('change', {bubbles: true}));
        };

        /**
         * Checks the current value and disables/enables buttons
         * based on the input's min/max attributes.
         */
        const updateButtonStates = () => {
          const minValue = parseFloat(input.min);
          const maxValue = parseFloat(input.max);
          const currentValue = parseFloat(input.value) || 0;

          btnDecrement.disabled = !isNaN(minValue) && currentValue <= minValue;
          btnIncrement.disabled = !isNaN(maxValue) && currentValue >= maxValue;
        };

        // Event listener for the increment button
        btnIncrement.addEventListener('click', () => {
          stepUp();
        });

        // Event listener for the decrement button
        btnDecrement.addEventListener('click', () => {
          stepDown();
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
