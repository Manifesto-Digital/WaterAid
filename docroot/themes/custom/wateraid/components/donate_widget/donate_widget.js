(function (Drupal) {
    document.addEventListener("DOMContentLoaded", function () {
      // Find all donation widgets within the current context (e.g., page or AJAX-loaded content)
      // that haven't been processed yet.
        const widgets = document.querySelectorAll('.donate-widget:not([data-donation-widget-processed])');

        widgets.forEach(widget => {
          // Mark this widget as processed to prevent attaching event listeners multiple times.
            widget.setAttribute('data-donation-widget-processed', 'true');

            const amountRadios = widget.querySelectorAll('input[name="amount"]');
            const customAmountContainer = widget.querySelector('.donate-widget__custom-amount');
            const customAmountInput = widget.querySelector('#custom_amount');
            const detailElements = widget.querySelectorAll('.donate-widget__amount-details--detail');

          // Exit if we don't have the necessary elements
            if (!amountRadios.length || !customAmountContainer || !customAmountInput || !detailElements.length) {
                return;
            }

          /**
           * Updates visibility of the custom amount field and the active detail description
           * based on the currently selected donation amount.
           */
            const updateAmountSelection = () => {
                const selectedRadio = widget.querySelector('input[name="amount"]:checked');

                if (!selectedRadio) {
                    return; // Do nothing if no radio is selected
                }

              // Toggle visibility of the custom amount text input
                if (selectedRadio.value === 'other') {
                    customAmountContainer.classList.remove('is-hidden');
                } else {
                    customAmountContainer.classList.add('is-hidden');
                    customAmountInput.value = ''; // Clear input when hidden
                }

              // Toggle the visible detail description
              // Note: The detail div ID is constructed as 'details-' + the radio's ID.
                const targetDetailId = 'details-' + selectedRadio.id;

                detailElements.forEach(detail => {
                    if (detail.id === targetDetailId) {
                        detail.classList.remove('is-hidden');
                    } else {
                        detail.classList.add('is-hidden');
                    }
                });
            };

          // Add a 'change' event listener to each radio button in the group.
            amountRadios.forEach(radio => {
                radio.addEventListener('change', updateAmountSelection);
            });

        // Run the function once on page load to set the initial state correctly.
        updateAmountSelection();

        // Hide impact tag after 10 seconds.
        setInterval(() => {
            const impactTags = document.querySelectorAll('.donate-widget__increase-impact');
            impactTags.forEach((impactTag) => {
                impactTag.classList.add('is-hidden');
              });
        }, 10000);
        });
    });
})(Drupal);
