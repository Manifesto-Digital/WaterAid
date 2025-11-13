(function (Drupal) {
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

      // Exit if we don't have the necessary elements
      if ((!amountRadios.length || !detailElements.length) &&
        !(oneOffCustomAmountContainer || monthlyCustomAmountContainer)
      ) {
        return;
      }

      /**
       * Updates visibility of the custom amount field and the active detail description
       * based on the currently selected donation amount.
       */
      const updateAmountSelection = () => {
        const selectedFrequency = widget.querySelector('input[name="frequency"]:checked');

        let selectedRadio = widget.querySelector('input[name="one_off_amount"]:checked');

        if (selectedFrequency.value === "monthly") {
          selectedRadio = widget.querySelector('input[name="monthly_amount"]:checked');
        }

        if (!selectedRadio) {
          return; // Do nothing if no radio is selected
        }

        // Toggle visibility of the custom amount text input
        if (selectedRadio.value === "other") {
          if (selectedFrequency.value === "monthly") {
            monthlyCustomAmountContainer?.classList.remove("is-hidden");
          } else {
            oneOffCustomAmountContainer?.classList.remove("is-hidden");
          }
        } else {
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
        const selectedFrequency = widget.querySelector('input[name="frequency"]:checked');

        if (!selectedFrequency) {
          return;
        }

        // Show / hide relevant donation amount options based on frequency selection.
        if (selectedFrequency.value === "monthly") {
          if (oneOffContainer) {
            oneOffContainer.classList.add("is-hidden");
          }
          monthlyContainer.classList.remove("is-hidden");

          // Set first radio as checked.
          const monthlyAmounts = monthlyContainer.querySelectorAll('input[name="monthly_amount"]');
          monthlyAmounts[0].checked = true;
          updateAmountSelection();
        } else {
          oneOffContainer.classList.remove("is-hidden");
          if (monthlyContainer) {
            monthlyContainer.classList.add("is-hidden");
          }

          // Set first radio as checked.
          const oneOffAmounts = oneOffContainer.querySelectorAll('input[name="one_off_amount"]');
          oneOffAmounts[0].checked = true;
          updateAmountSelection();
        }
      };

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
        let amountValue = selectedRadio.value;

        if (monthlyCustomAmountInput.value || oneOffCustomAmountInput.value) {
          if (selectedFrequency.value === "monthly") {
            amountValue = monthlyCustomAmountInput.value;
          } else {
            amountValue = oneOffCustomAmountInput.value;
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

    });
  });
})(Drupal);
