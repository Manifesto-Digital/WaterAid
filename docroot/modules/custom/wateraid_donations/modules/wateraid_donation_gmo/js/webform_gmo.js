/**
 * @file
 * Javascript behaviors for webform GMO elements.
 */

(function ($, Drupal, drupalSettings, Backbone, Multipayment) {
  'use strict';

  /**
   * Declare view to allow GMO component to interact with the payment model.
   */
  Drupal.wateraidDonationForms.GmoView = Backbone.View.extend({
    initialize: function () {
      this.listenTo(this.model, 'change:paymentMethod', this.handleMountBehaviour);
      this.listenTo(this.model, 'submitForm', this.submitFormHandler);
      this.listenTo(this.model, 'refresh', this.handleMountBehaviour);
      this.mounted = false;
    },
    // Set inputs within a given wrapper to be either required or optional.
    setInputState: function (wrapper, state) {
      const inputs = wrapper.querySelectorAll('input');
      inputs.forEach(function (input) {
        const parent = input.closest('.form-item');
        const label = parent.querySelector('label');
        const parentFieldset = parent.closest('fieldset');
        const legend = parentFieldset.querySelector('.fieldset-legend');
        // Sets inputs to be required - allows fields to be validated in the
        // front-end.
        if (state == 'required') {
          label.classList.add('form-required');
          legend.classList.add('form-required');
          input.setAttribute('aria-invalid', 'true');
          input.setAttribute('required', 'required');
        // Sets inputs to be optional and clears values - prevents form values
        // being submitted to the back-end.
        } else if (state == 'optional') {
          input.removeAttribute('required');
          label.classList.remove('form-required');
          legend.classList.remove('form-required');
          input.removeAttribute('aria-invalid');
          input.value = '';
          // Remove any state classes and messages.
          if (input.classList.contains('valid')) {
            input.classList.remove('valid');
          }
          if (input.classList.contains('error')) {
            const errorLabel = parent.querySelector('label.error');
            input.classList.remove('error');
            errorLabel.remove();
          }
        }
      });
    },
    handleMountBehaviour: function () {
      if (this.model.isCurrentPaymentMethod(this)) {
        this.setInputState(this.el, 'required');
        if (!this.mounted) {
          this.mounted = true;
        }
      }
      else {
        this.setInputState(this.el, 'optional');
        if (this.mounted) {
          this.mounted = false;
        }
      }

      /**
       * Add jQuery Validate custom rule to ensure 'other' amount fields do not
       * exceed predefined max values.
       */
      const frequency = Drupal.wateraidDonationForms.model.attributes.frequency;
      const amount = Drupal.wateraidDonationForms.model.attributes.amount;
      const method = Drupal.wateraidDonationForms.model.attributes.paymentMethod;
      const max = Drupal.wateraidDonationForms.model.attributes.amounts[frequency]['payment_methods_max'][method];
      const oneOffOtherField = document.getElementById('edit-donation-amount-amount-one-off-amounts-other');
      const recurringOtherField = document.getElementById('edit-donation-amount-amount-recurring-amounts-other');
      const otherFields = [oneOffOtherField, recurringOtherField];

      $.validator.addMethod('otherAmountMax', function (value) {
        return value <= Number(max)
      }, Drupal.t('Sorry, the maximum donation amount for this payment method is @max', {'@max': max}));

      $('.webform-submission-form').validate();
      otherFields.forEach(function (field) {
        $(field).rules( 'add', {
          otherAmountMax: true
        });
      });
    },
    // Display a modal for users to confirm form inputs before submission.
    displayReviewModal: function () {
      this.model.modalDisplayed = true;
      const form = document.querySelector('.webform-submission-form');

      // Get all the form inputs and obtain their labels and values.
      const getInputData = function () {
        const data = {};
        const inputs = form.querySelectorAll('textarea, input');
        inputs.forEach(function (input) {
          // Only target inputs that are visible and have values.
          const isVisible = input.offsetWidth > 0 && input.offsetHeight > 0;
          const hasValue = input.value;
          if (isVisible && hasValue) {
            // Exclude any payment fields. We don't want to display these in
            // the summary.
            const excludedFields = [
              'edit-donation-amount-amount-one-off-amounts-other',
              'edit-donation-amount-amount-recurring-amounts-other',
              'gmo-cardholder-name-one-off',
              'gmo-card-one-off',
              'gmo-expiration-month-one-off',
              'gmo-expiration-year-one-off',
              'gmo-security-code-one-off',
              'gmo-cardholder-name-recurring',
              'gmo-card-recurring',
              'gmo-expiration-month-recurring',
              'gmo-expiration-year-recurring',
              'gmo-security-code-recurring'
            ];

            if (excludedFields.indexOf(input.id) == -1) {
              // Also check whether radios and checkboxes are selected. There are
              // different selectors we need to use to get the relevant labels
              // and input values.
              if (input.type === 'radio' || input.type === 'checkbox') {
                if (input.checked) {
                  const fieldset = input.closest('fieldset');
                  if (fieldset) {
                    let label = fieldset.querySelector('.fieldset-legend').innerText;
                    let value = '';
                    let labelClassName = '';

                    // UI checkbox / radios have a different markup structure,
                    // so need to target a different element for the value.
                    if (input.classList.contains('ui-checkboxradio')) {
                      // If 'other' is selected for the donation amount, then get
                      // that value instead of the label.
                      if (input.value == '_other_') {
                        const radioWrapper = input.closest('.form-radios');
                        const amountWrapper = radioWrapper.nextElementSibling;
                        const otherAmount = amountWrapper.querySelector('.form-number');
                        value = otherAmount.value;
                      }
                      else {
                        value = input.nextSibling.innerText;
                      }
                    }
                    else {
                      // Use preceding description text for agree checkbox label.
                      if (input.getAttribute('id') == 'edit-agree') {
                        const description = input.closest('fieldset').querySelector('.form-item-privacy-policy-text p');
                        label = description ? description.innerText : Drupal.t('Please confirm the privacy policy and agree');
                      // Visually hide label for email signup field.
                      } else if (input.getAttribute('id') == 'edit-e-newsletter') {
                        label = Drupal.t('Email newsletters');
                        labelClassName = 'visually-hidden';
                      }
                      value = input.closest('.form-item').querySelector('label.option').innerText;
                    }

                    const id = input.getAttribute('data-drupal-selector') + '-preview';
                    const parentFieldset = fieldset.closest('.webform-type-fieldset, .donations-webform-amount--wrapper');
                    const parentFieldsetTitle = parentFieldset.querySelector('.fieldset-legend').innerText;

                    // Check if the fieldset already exists on the data object.
                    // Create a nested object for it (if it doesn't exist).
                    if (!data.hasOwnProperty(parentFieldsetTitle)) {
                      data[parentFieldsetTitle] = {}
                    }
                    if (!data[parentFieldsetTitle].hasOwnProperty(id)) {
                      data[parentFieldsetTitle][id] = {}
                    }
                    data[parentFieldsetTitle][id]['value'] = value;
                    data[parentFieldsetTitle][id]['label'] = label;

                    if (labelClassName) {
                      data[parentFieldsetTitle][id]['labelClassName'] = labelClassName;
                    }
                  }
                }
              } else {
                const fieldset = input.closest('fieldset');
                if (fieldset) {
                  const fieldsetTitle = fieldset.querySelector('.fieldset-legend').innerText;
                  const label = input.closest('.form-item').querySelector('label').innerText;
                  const id = input.getAttribute('data-drupal-selector') + "-preview";
                  if (!data.hasOwnProperty(fieldsetTitle)) {
                    data[fieldsetTitle] = {}
                  }
                  if (!data[fieldsetTitle].hasOwnProperty(id)) {
                    data[fieldsetTitle][id] = {}
                  }
                  data[fieldsetTitle][id]['value'] = input.value;
                  data[fieldsetTitle][id]['label'] = label;
                }
              }
            }
          }
        });

       return data;
      }

      // Create modal markup.
      const modal = document.createElement('div');
      const modalTitle = document.createElement('h2');
      modalTitle.innerText = Drupal.t('Please review your form submission');
      modal.append(modalTitle);

      // Populate form data into modal markup.
      const formData = getInputData();
      const formFieldsets = Object.entries(formData);
      formFieldsets.forEach(function (fieldset) {
        const sectionHeading = document.createElement('h3');
        sectionHeading.innerText = fieldset[0].replace('?', '');
        const list = document.createElement('dl');
        const fieldSetData = Object.entries(fieldset[1]);
        fieldSetData.forEach(function (fieldData) {
          const term = document.createElement('dt');
          const description = document.createElement('dd');
          term.innerText = fieldData[1]['label'].replace('?', '');
          if (fieldData[1]['labelClassName']) {
            term.classList.add(fieldData[1]['labelClassName']);
          }
          description.innerText = fieldData[1]['value'];
          description.classList.add(fieldData[0]);
          list.append(term, description);
        });
        modal.append(sectionHeading, list);
      })

      // Create submit and cancel buttons.
      const buttonWrapper = document.createElement('div');
      const cancelButton = document.createElement('button');
      const confirmButton = document.createElement('button');
      buttonWrapper.classList.add('donation-modal__button-wrapper');
      cancelButton.setAttribute('type', 'button');
      cancelButton.innerText = Drupal.t('Amend');
      confirmButton.setAttribute('type', 'button');
      confirmButton.innerText = Drupal.t('Submit');
      buttonWrapper.append(cancelButton, confirmButton);
      modal.append(buttonWrapper);

      // Open modal.
      const options = {
        width: '90%',
        classes: {
          'ui-dialog': 'donation-modal'
        }
      };
      const drupalDialog = Drupal.dialog(modal, options);
      drupalDialog.show();
      const context = this;

      // Set up cancel event handler, which allows form to be amended.
      cancelButton.addEventListener('click', function () {
        drupalDialog.close();
        context.model.modalDisplayed = false;
      });

      // Set up confirm event handler, will allows form to be submitted.
      confirmButton.addEventListener('click', function () {
        drupalDialog.close();
        context.model.modalDisplayed = false;
        context.model.modalConfirmed = true;
        const submitButton = document.querySelector('.button--primary .webform-button--submit');
        submitButton.click();
      });

    },
    // Handle form submit behaviour.
    submitFormHandler: function (event) {
      if (this.mounted) {
        // Prevent form from submitting until salesforceSuccess flag is true.
        if (!this.model.salesforceSuccess) {
          event.preventDefault();

          // Only proceed if the non-payment form fields are valid.
          if ($('.webform-submission-form').valid()) {
            // If fields are valid, show modal for user to confirm form inputs
            // before proceeding.
            if (!this.model.modalDisplayed && !this.model.modalConfirmed) {
              this.displayReviewModal();
              return false;
            }

            // Only proceed if modal inputs have been confirmed.
            if (!this.model.modalConfirmed) {
              return false;
            }

            // Do not proceed if createPaymentMethod is already initiated and
            // payment is already in progress.
            if (this.model.getDisableCreatePaymentMethod() === true) {
              return false;
            }

            // Ensure we're only altering the submit event when dealing with GMO
            // payments:
            // 1. Card payment submission.
            if (['gmo', 'gmo_subscription'].includes(this.model.attributes.paymentMethod)) {
              if (document.getElementById('payment-response-result').value === '') {
                this.createPaymentMethod(event);
              }
            }
            // 2. Bank transfer submission.
            else if (this.model.attributes.paymentMethod == 'gmo_bank_transfer') {
              this.setLoadingState('loading');
              this.clearAndSubmit(this);
            }
          } else {
            document.querySelector('.form-item.error').scrollIntoView({ block: 'center' });
          }
        }
      }
    },
    // Removing any existing salesforce error messages at top of page.
    removeStatusMessageError: function () {
      const existingMessage = document.querySelector('[data-drupal-message-id="donation-form-error"]');
      if (existingMessage) {
        existingMessage.parentNode.removeChild(existingMessage);
      }
    },
    // Display any salesforce error messages at top of page.
    showStatusMessageError: function (errorMessage) {
      errorMessage = errorMessage || Drupal.t('We were unable to take your payment. Please check your details and try again.');
      const messages = new Drupal.Message();
      const errorMessageWrapper = document.querySelector('.messages__wrapper');
      messages.add(errorMessage, { type: 'error', id:'donation-form-error' });
      const errorMessageElement = document.querySelector('.messages--error');
      errorMessageElement.classList.add('control-width', 'status-messages', 'status-message-margin-top');
      errorMessageWrapper.scrollIntoView({ block: 'center' });

      this.handleMountBehaviour();
      this.model.modalConfirmed = false;
    },
    // Remove any card error messages next to card input fields.
    removeInlineErrors: function () {
      const paymentSection = document.getElementById('edit-payment--wrapper');
      const inputs = document.querySelectorAll('input.error', paymentSection);
      inputs.forEach(function (input) {
        const parent = input.closest('.form-item');
        input.classList.remove('error');
        const errors = parent.querySelectorAll('label.error');
        errors.forEach(function (label) {
          label.remove();
        });
      });
    },
    // Display any card error messages next to card input fields.
    showInlineError: function (input, errorMessage) {
      const parent = input.closest('.form-item');
      let errorLabel = parent.querySelector('label.error');

      // If there is an existing error label, use that to display new message.
      // Otherwise, create a new label.
      if (!errorLabel) {
        errorLabel = document.createElement('label');
        errorLabel.classList.add('error');
        errorLabel.setAttribute('for',  input.getAttribute('id'));
      }

      // Only add message if one is passed in to display.
      if (errorMessage) {
        errorLabel.innerText = errorMessage;
        errorLabel.style.display = 'block';
        input.after(errorLabel);
      }

      input.classList.add('error');
      input.classList.remove('valid');
      input.setAttribute('aria-invalid', true);
      input.scrollIntoView({ block: 'center' });
      this.model.modalConfirmed = false;
    },
    // Process the form submission.
    clearAndSubmit: function (context) {
      const form = document.querySelector('.webform-submission-form');
      Array.from(document.getElementsByClassName("clear-on-submit")).forEach(function (element) {
        element.value = '';
      });
      form.submit();
    },
    // Create card payment intent.
    createPaymentMethod: function () {
      this.setLoadingState('loading');

      const frequencyIdSuffix = function () {
        switch (Drupal.wateraidDonationForms.model.attributes.frequency) {
          case 'one_off':
            return '-one-off';

          case 'recurring':
            return '-recurring';

        }
      };

      const shopId = drupalSettings.webformGmo.shop_id;
      const cardNumber = document.getElementById('gmo-card' + frequencyIdSuffix());
      const expiryYear = document.getElementById('gmo-expiration-year' + frequencyIdSuffix());
      const expiryMonth = document.getElementById('gmo-expiration-month' + frequencyIdSuffix());
      const securityCode = document.getElementById('gmo-security-code' + frequencyIdSuffix());
      const carholderName = document.getElementById('gmo-cardholder-name' + frequencyIdSuffix());
      const paymentToken = document.getElementById('gmo-payment-token' + frequencyIdSuffix());

      Multipayment.init(shopId);
      Multipayment.getToken(
        {
          cardno: cardNumber.value,
          expire: expiryYear.value + expiryMonth.value,
          securitycode: securityCode.value,
          holdername: carholderName.value
        },(response) => {
          switch(response.resultCode) {
            case '000':
              // Success - populate the token field and submit.
              paymentToken.setAttribute('value', response.tokenObject.token);
              this.clearAndSubmit(this);
              break;

            case 100:
            case 101:
              this.setLoadingState('complete');
              if (cardNumber.value == '') {
                this.showInlineError(cardNumber, Drupal.t('Card number field is required.'));
              } else {
                this.showInlineError(cardNumber, Drupal.t('Card number should only contain numeric characters.'));
              }
              break;

            case 102:
              this.setLoadingState('complete');
              this.showInlineError(cardNumber, Drupal.t('Card number should be between 10 - 16 digits.'));
              break;

            case 110:
            case 111:
            case 112:
            case 113:
              this.setLoadingState('complete');
              if (expiryYear.value + expiryMonth.value == '') {
                this.showInlineError(expiryMonth, Drupal.t('Expiry date field is required.'));
              } else {
                this.showInlineError(expiryMonth, Drupal.t('Please enter an expiry date in the format MM / YYYY.'));
              }
              this.showInlineError(expiryYear);
              break;

            case 121:
            case 122:
              this.setLoadingState('complete');
              this.showInlineError(securityCode, Drupal.t('Please enter a valid security code.'));
              break;

            case 131:
              this.setLoadingState('complete');
              this.showInlineError(carholderName, Drupal.t('Cardholder name should not contain symbols or alphanumeric characters.'));
              break;

            default:
              this.setLoadingState('complete');
              this.showStatusMessageError();
          }
        }
      );
    },
    // Handle state of loading spinner and submit button.
    setLoadingState: function (state) {
      const donationSubmit = document.querySelector('.webform-button--submit');
      const donationSubmitLoader = document.querySelector('.webform-button--loading');
      if (state == 'loading') {
        // Remove any previous errors.
        this.removeStatusMessageError();
        this.removeInlineErrors();
        // Show loading spinner.
        if (donationSubmitLoader) {donationSubmitLoader.style.display = 'block';}
        // Disable submit button.
        donationSubmit.setAttribute('disabled', 'disabled');
        // Set disable payment flag.
        this.model.setDisableCreatePaymentMethod(true);
      }
      else if (state == 'complete') {
        // Remove loading spinner.
        if (donationSubmitLoader) {donationSubmitLoader.style.display = 'none';}
        // Enable submit button.
        donationSubmit.removeAttribute('disabled');
        // Remove disable payment flag.
        this.model.setDisableCreatePaymentMethod(false);
      }
    }
  });

})(jQuery, Drupal, drupalSettings, Backbone, Multipayment);
