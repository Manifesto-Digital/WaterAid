((Drupal) => {
  'use strict';

  Drupal.behaviors.donationForm = {
    attach(context) {
      const webformSubmissionForm = context.querySelector(
        '.webform-submission-form'
      );

      window.addEventListener('load', () => {
        const webformDonationsPage = context.querySelector(
          '.webform-donations-page'
        );
        const webformDonationsParagraph = context.querySelector(
          '.paragraph--type--donation-cta-widget-embed'
        );

        // Identify a donation page that is not a CTA donation page.
        if (
          webformSubmissionForm &&
          webformDonationsPage &&
          !webformDonationsParagraph
        ) {

          // Add focus on first form element.
          // If first form element is radio button check whether radio button is
          // already checked and add focus to that one, otherwise add focus to the
          // first radio button.
          // Do not include the back button as this will appear first but should
          // not be the default focus.
          const formElements = context.querySelectorAll(
            'input:not([hidden]):not(.webform-button--previous), textarea:not([hidden]), select:not([hidden])'
          );

          for (let i = 0; i < formElements.length; i++) {
            if (formElements[i].offsetParent !== null) {
              const radios = formElements[i].closest('.form-radios');
              if (
                formElements[i].classList.contains('form-radio') && radios &&
                radios.querySelectorAll('.form-radio[checked="checked"]').length
              ) {
                radios
                  .querySelectorAll('.form-radio[checked="checked"]')
                  .forEach((radio) => {
                    if (radio.hasAttribute('checked')) {
                      radio.focus();
                      return;
                    }
                  });
              } else {
                formElements[i].focus();
              }
              break;
            }
          }
        }

        // Check how many child elements (form-items) does one-off selection
        // payment methods element (.form-radios) have.
        // If it has only one payment method (form-item), add custom class to
        // it, so that the width of the label::after can be set to 100% in css.
        // If three options, add checked class to the first form-radio input
        // and let css do the rest.
        if (webformSubmissionForm) {
          const oneOffPaymentMethods = document.getElementById(
            'edit-payment-payment-methods-one-off-selection'
          );
          const donationFrequency = document.getElementById('edit-donation-amount-frequency');

          // Establish if the element is the frequency or payment field
          let elementToStyle = '';
          if (oneOffPaymentMethods) {
            elementToStyle = oneOffPaymentMethods;
          }
          else if (donationFrequency) {
            elementToStyle = donationFrequency;
          }

          if (elementToStyle) {
            // Don't include elements the user can't see
            let invisibleElements = 0;
            for (let child of elementToStyle.children) {
              if (child.classList.contains('visually-hidden') || getComputedStyle(child, null).display == 'none') {
                invisibleElements ++;
              }
            }

            switch (elementToStyle.children.length - invisibleElements) {
              case 1:
                elementToStyle.classList.add('single-option');
                break;
              case 3:
                elementToStyle.classList.add('three-options');
                const radios = elementToStyle.querySelectorAll(
                  '.wa-donation-method-selection'
                );
                const checkedStr = 'checked-';
                radios.forEach((radio, i) => {
                  if (radio.checked) {
                    radios[0].classList.add(checkedStr + i);
                  }
                  radio.addEventListener('change', () => {
                    if (!radios[0].classList.contains(checkedStr + i)) {
                      for (let className of radios[0].classList) {
                        if (className.startsWith(checkedStr)) {
                          radios[0].classList.remove(className);
                          break;
                        }
                      }
                      radios[0].classList.add(checkedStr + i);
                    }
                  });
                });
                break;
            }
          }
        }
      });

      // Custom webform in memory mandatory fields.
      const inMemory = context.querySelector(
        '.js-webform-type-donations-webform-in-memory'
      );

      if (inMemory) {
        const selectedTitle = inMemory.querySelector(
          'select[name="in_memory[in_memory_title]"]'
        );
        const firstName = inMemory.querySelector(
          '.js-form-item-in-memory-in-memory-firstname'
        );
        const lastName = inMemory.querySelector(
          '.js-form-item-in-memory-in-memory-lastname'
        );

        const changeElCondition = (el, required) => {
          const label = el.querySelector('label');
          const input = el.querySelector('.form-text');

          if (required) {
            label.classList.add('js-form-required', 'form-required');
            label.innerHTML = label.innerHTML.split(' ' + Drupal.t('(optional)'))[0];
            input.required = true;
          } else {
            const alertMsg = el.querySelector('label[role="alert"]');
            label.classList.remove('js-form-required', 'form-required');
            label.innerHTML += (' ' + Drupal.t('(optional)'));
            input.required = false;
            input.classList.remove('error');
            if (alertMsg) {
              el.removeChild(alertMsg);
            }
          }
        };

        // Check if title or first name are populated on window load in order
        // to show last name as required field (if user is moving between
        // steps).
        if (
          selectedTitle.value !== '' ||
          firstName.querySelector('.form-text').value !== ''
        ) {
          changeElCondition(lastName, true);
        }

        // If title (miss, mr, etc.) is selected - make lastName required.
        // If title (none) and firstName are empty - make lastName optional.
        selectedTitle.addEventListener('change', (e) => {
          if (e.target.value !== '') {
            changeElCondition(lastName, true);
          } else if (firstName.querySelector('.form-text').value === '') {
            changeElCondition(lastName, false);
          }
        });

        // If firstName is not empty - make lastName required.
        // If title (none) and firstName are empty - make lastName optional.
        firstName.addEventListener('input', (e) => {
          if (e.target.value !== '') {
            changeElCondition(lastName, true);
          } else if (selectedTitle.value === '') {
            changeElCondition(lastName, false);
          }
        });
      }
    }
  };
})(Drupal);
