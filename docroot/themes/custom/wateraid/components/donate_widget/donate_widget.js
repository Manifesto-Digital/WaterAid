(function (Drupal, drupalSettings) {
  document.addEventListener("DOMContentLoaded", function () {
    // Find all donation widgets within the current context (e.g., page or AJAX-loaded content)
    // that haven't been processed yet.
    const widgets = document.querySelectorAll(".donate-widget:not([data-donation-widget-processed])");

    widgets.forEach((widget) => {
      // Mark this widget as processed to prevent attaching event listeners multiple times.
      widget.setAttribute("data-donation-widget-processed", "true");

      const amountRadios = widget.querySelectorAll('input[name="one_off_amount"]');
      const monthlyAmountRadios = widget.querySelectorAll('input[name="monthly_amount"]');
      const detailElements = widget.querySelectorAll(".donate-widget__amount-details--detail");
      const frequencyRadios = widget.querySelectorAll('input[name="frequency"]');
      const oneOffContainer = widget.querySelector(".donate-widget__options-group--one-off");
      const monthlyContainer = widget.querySelector(".donate-widget__options-group--monthly");
      const monthlyCustomAmountContainer = widget.querySelector(".donate-widget__custom-amount--monthly");
      const monthlyCustomAmountInput = widget.querySelector("#custom_amount_monthly");
      const oneOffCustomAmountContainer = widget.querySelector(".donate-widget__custom-amount--one-off");
      const oneOffCustomAmountInput = widget.querySelector("#custom_amount_one_off");
      const submitButton = widget.querySelector('#donate_submit');

      const minDonationAmount = parseInt(drupalSettings.donate_widget?.minimum_donation);
      let selectedFrequency;
      let amountValue;

      // Add event listener on input changes.
      monthlyCustomAmountInput?.addEventListener("input", () => {
        // Only allow numbers and decimal point
        monthlyCustomAmountInput.value = monthlyCustomAmountInput.value.replace(/[^0-9.]/g, '');
        amountValue = monthlyCustomAmountInput.value;
        // Remove error state when user starts typing
        submitButton.classList.remove('error');
        const existingError = widget.querySelector('label.error[for="donate_submit"]');
        if (existingError) {
          existingError.remove();
        }
        updateDonationSummary();
      });

      oneOffCustomAmountInput?.addEventListener("input", () => {
        // Only allow numbers and decimal point
        oneOffCustomAmountInput.value = oneOffCustomAmountInput.value.replace(/[^0-9.]/g, '');
        amountValue = oneOffCustomAmountInput.value;
        // Remove error state when user starts typing
        oneOffCustomAmountInput.classList.remove('error');
        const existingError = widget.querySelector('label.error[for="donate_submit"]');
        if (existingError) {
          existingError.remove();
        }
        updateDonationSummary();
      });

      // Exit if we don't have the necessary elements
      if ((!amountRadios.length || !detailElements.length) &&
        !(oneOffCustomAmountContainer || monthlyCustomAmountContainer)
      ) {
        return;
      }


      // Check if mobile or tablet detected.
      function hasTouchSupport() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
      }

      // Check device operating system - Android or IOS
      const isMobile = {
        Android: function () {
          return navigator.userAgent.match(/Android/i);
        },
        iOS: function () {
          return navigator.userAgent.match(/iPhone|iPad|iPod/i);
        },
      };

      const payOptions = () => {

        // Hide GooglePay and ApplePay if not compatible.
        if (!hasTouchSupport()) {
          const applePay = widget.querySelector('.wa-apple');
          const googlePay = widget.querySelector('.wa-google');
          applePay.style.display = 'none';
          googlePay.style.display = 'none';
        }
        // Hide ApplePay on Android device.
        if (hasTouchSupport() && isMobile.Android()) {
          const applePay = widget.querySelector('.wa-apple');
          const googlePay = widget.querySelector('.wa-google');
          applePay.style.display = 'none';
          googlePay.style.display = 'block';
        }
        // Hide GooglePay on IOS device.
        if (isMobile.iOS() || window.ApplePaySession) {
          const applePay = widget.querySelector('.wa-apple');
          const googlePay = widget.querySelector('.wa-google');
          applePay.style.display = 'block';
          googlePay.style.display = 'none';
        }
      }

      /**
       * Updates visibility of the custom amount field and the active detail description
       * based on the currently selected donation amount.
       */
      const updateAmountSelection = () => {
        selectedFrequency = widget.querySelector('input[name="frequency"]:checked');

        let selectedRadio = widget.querySelector('input[name="one_off_amount"]:checked');

        if (selectedFrequency.value === "monthly") {
          selectedRadio = widget.querySelector('input[name="monthly_amount"]:checked');
        }

        if (!selectedRadio) {
          return; // Do nothing if no radio is selected
        }

        // Toggle visibility of the custom amount text input
        if (selectedRadio.value === "other") {
          amountValue = '';
          if (selectedFrequency.value === "monthly") {
            monthlyCustomAmountContainer?.classList.remove("is-hidden");
          } else {
            oneOffCustomAmountContainer?.classList.remove("is-hidden");
          }
        } else {
          amountValue = selectedRadio.value
          monthlyCustomAmountContainer?.classList.add("is-hidden");
          if (monthlyCustomAmountInput) {
            monthlyCustomAmountInput.value = ""; // Clear input when hidden
          }
          oneOffCustomAmountContainer?.classList.add("is-hidden");
          if (oneOffCustomAmountInput) {
            oneOffCustomAmountInput.value = ""; // Clear input when hidden
          }
        }

        // Toggle the visible detail description
        // Note: The detail div ID is constructed as 'details-' + the radio's ID.
        const targetDetailId = "details-" + selectedRadio.id;

        detailElements.forEach((detail) => {
          if (detail.id === targetDetailId) {
            detail.classList.remove("is-hidden");
          } else {
            detail.classList.add("is-hidden");
          }
        });

        updateDonationSummary();
      };

      // Add a 'change' event listener to each radio button in the group.
      amountRadios.forEach((radio) => {
        radio.addEventListener("change", updateAmountSelection);
      });
      monthlyAmountRadios.forEach((radio) => {
        radio.addEventListener("change", updateAmountSelection);
      });

      /**
       * Updates donation amounts based on frequency selection.
       */
      const updateFrequencySelection = () => {
        selectedFrequency = widget.querySelector('input[name="frequency"]:checked');

        if (!selectedFrequency) {
          return;
        }

        // Update the logos that are displayed.
        const apple = widget.querySelector('.wa-apple');
        const visa = widget.querySelector('.wa-visa');
        const mastercard = widget.querySelector('.wa-mastercard');
        const paypal = widget.querySelector('.wa-paypal');
        const google = widget.querySelector('.wa-google');
        const direct = widget.querySelector('.wa-direct-debit');
        const fundraiser = widget.querySelector('.donate-widget__fr-logo');

        const isAus = document.querySelector('.group--wateraid-australia') !== null;
        if (selectedFrequency.value === 'monthly') {
          if (isAus) {
            apple.style.display = 'none';
            visa.style.display = 'block';
            mastercard.style.display = 'block';
            paypal.style.display = 'none';
            google.style.display = 'none';
            direct.style.display = 'none';
            fundraiser.style.display = 'none';
          }
          else {
            apple.style.display = 'none';
            visa.style.display = 'none';
            mastercard.style.display = 'none';
            paypal.style.display = 'none';
            google.style.display = 'none';
            direct.style.display = 'block';
          }
        }
        else {
          apple.style.display = 'block';
          visa.style.display = 'block';
          mastercard.style.display = 'block'
          paypal.style.display = 'block';
          google.style.display = 'block';
          direct.style.display = 'none';
        }

        payOptions();

        // Show / hide relevant donation amount options based on frequency selection.
        if (selectedFrequency.value === "monthly") {
          if (oneOffContainer) {
            oneOffContainer.classList.add("is-hidden");
          }
          monthlyContainer.classList.remove("is-hidden");

          updateAmountSelection();
        } else {
          oneOffContainer.classList.remove("is-hidden");
          if (monthlyContainer) {
            monthlyContainer.classList.add("is-hidden");
          }

          updateAmountSelection();
        }
        updateDonationSummary()
      };

      /**
       * Updates donation summary display.
       */
      const updateDonationSummary = () => {
        const summary = widget.querySelector('.donate-widget__donation-summary');
        // select donation summery
        summary.classList.remove('show');

        // If both are found, update the summary display.
        if (selectedFrequency.value && amountValue) {
          summary.classList.add('show');
          // Find donation frequency and amount
          summary.querySelector('.donate-widget__donation-summary__label').textContent =
            selectedFrequency.value === 'monthly'
              ? Drupal.t('You are making a regular donation of:')
              : Drupal.t('You are making a one-off donation of:');

          // Get amount prefix if exists, eg.: £.
          summary.querySelector('.donate-widget__donation-summary__amount').textContent =
            `${drupalSettings.donate_widget?.currency_prefix || ''}${amountValue} ${selectedFrequency.value === 'monthly' ? Drupal.t('per month') : ''}`;

        }
      }

      // Add a 'change' event listener to frequency radios.
      frequencyRadios.forEach((radio) => {
        radio.addEventListener("change", updateFrequencySelection);
      });

      const donateRedirection = (event) => {
        event.preventDefault();
        const selectedFrequency = widget.querySelector('input[name="frequency"]:checked');
        let selectedRadio = widget.querySelector('input[name="one_off_amount"]:checked');

        if (selectedFrequency.value === "monthly") {
          selectedRadio = widget.querySelector('input[name="monthly_amount"]:checked');
        }

        const location = submitButton.getAttribute('data-location');
        const frequencyValue = selectedFrequency.value;
        amountValue = selectedRadio.value;

        if (monthlyCustomAmountInput?.value || oneOffCustomAmountInput?.value) {
          if (selectedFrequency.value === "monthly") {
            amountValue = monthlyCustomAmountInput.value;
          } else {
            amountValue = oneOffCustomAmountInput.value;
          }
        }

        // Validate custom amount fields
        if (minDonationAmount && selectedRadio.value === "other") {
          const floatDonateAmount = parseFloat(amountValue);
          if (isNaN(floatDonateAmount) || floatDonateAmount < minDonationAmount) {
            if (widget.querySelector('label.error[for="donate_submit"]')) {
              return;
            }

            const errorLabel = document.createElement('label');
            errorLabel.className = 'error';
            errorLabel.setAttribute('for', 'donate_submit');
            errorLabel.setAttribute('role', 'alert');
            errorLabel.textContent = Drupal.t('Please enter a value greater than or equal to ') + `${minDonationAmount}.`;
            submitButton.parentNode.after(errorLabel);
            submitButton.classList.add('error');

            return;
          }
        }

        const redirectUrl = location + '?fq=' + frequencyValue.replace('-', '_').replace('monthly', 'recurring') + '&val=' + amountValue;

        window.location.href = redirectUrl;

      };

      submitButton.addEventListener('click', donateRedirection);

      // Run the function once on page load to set the initial state correctly.
      updateAmountSelection();
      updateFrequencySelection();

      // Hide impact tag after 10 seconds.
      setInterval(() => {
        const impactTags = document.querySelectorAll(".donate-widget__increase-impact");
        impactTags.forEach((impactTag) => {
          impactTag.classList.add("is-hidden");
        });
      }, 10000);
    });
  });
})(Drupal, drupalSettings);
