/**
 * @file
 * Javascript behaviors for Bank Account (Direct Debit) webform element lookup.
 */

(function ($, Drupal, drupalSettings, Backbone) {

  'use strict';

  // Check if any validation errors are displayed
  function errorsExist(id) {
    let wrapperError = $('[data-drupal-selector="' + id + '--wrapper-error"]');
    let sortCodeError = $('[data-drupal-selector="' + id + '-sort-code-error"]');
    let accountError = $('[data-drupal-selector="' + id + '-account-error"]');
    return (
      wrapperError.html() && wrapperError.html().length ||
      sortCodeError.html() && sortCodeError.html().length ||
      accountError.html() && accountError.html().length
    );
  }

  /**
   * Declare view to allow stripe component to interact with the payment model.
   */
  Drupal.wateraidDonationForms.DDView = Backbone.View.extend({
    initialize: function () {
      // this.listenTo(this.model, 'change:paymentMethod', this.render);
      // this.listenTo(this.model, 'backStep', this.backStepHandler);
      this.listenTo(this.model, 'nextStep', this.nextStepHandler);
      this.listenTo(this.model, 'submitForm', this.submitFormHandler);

      if (drupalSettings.webformBankAccount['active'] === true) {
        this.pcaApiKey = drupalSettings.webformBankAccount['apiKey'];

        // Remove suffix from the ID.
        this.id = drupalSettings.webformBankAccount['id'].replace(/--.[a-zA-Z0-9_-]{0,10}$/, "");
        this.bankAccount = '';
        this.sortCode = '';

        // Add JS behaviour.
        // @todo re-instate when validation plays nicely with jQuery validate.
        this.addBehaviour();
      }
      this.ajaxrunning = false;
    },
    events: {
      'blur .account-number': 'BankAccountLostFocus',
      'blur .sort-code': 'SortCodeLostFocus'
    },
    addBehaviour: function () {
      // Set the location of the general error message from the validation.
      $('[data-drupal-selector="edit-payment-payment-methods-recurring"]')
          .append('<label data-drupal-selector="' + this.id + '--wrapper-error" class="error"></label>');

      // Manage the bank account number.
      $('[data-drupal-selector="' + this.id + '-account"]')
        .after('<label data-drupal-selector="' + this.id + '-account-error" class="error"></label>');

      // Manage the sort code number.
      $('[data-drupal-selector="' + this.id + '-sort-code"]')
        .after('<label data-drupal-selector="' + this.id + '-sort-code-error" class="error"></label>');

      // Make first start date field required.
      let radio = $('.form-item-payment-payment-methods-recurring-bank-account-bank-account-start-date .webform-radio');
      radio.find('input').first().prop('required', true);
      radio.find('input').first().attr('data-msg-required', Drupal.t('Please choose your first payment date.'));
    },
    nextStepHandler: function (event) {
      if (this.ajaxrunning || this.errorsExist()) {
        event.preventDefault(event);
      }
    },
    submitFormHandler: function (event) {
      // Only submit the form if there are no errors from the bank details.
      if (this.ajaxrunning || this.errorsExist()) {
        event.preventDefault(event);
      }
    },
    clearAccountErrorMessage: function () {
      $('[data-drupal-selector="' + this.id + '-account-error"]').hide();
      $('[data-drupal-selector="' + this.id + '-account"]').removeClass('error').removeClass('valid');
    },
    clearSortCodeErrorMessage: function () {
      $('[data-drupal-selector="' + this.id + '-sort-code-error"]').hide();
      $('[data-drupal-selector="' + this.id + '-sort-code"]').removeClass('error').removeClass('valid');
    },
    showAccountValid: function () {
      $('[data-drupal-selector="' + this.id + '-account-error"]').empty().hide();
      $('[data-drupal-selector="' + this.id + '-account"]').removeClass('error').addClass('valid');
    },
    showSortCodeValid: function () {
      $('[data-drupal-selector="' + this.id + '-sort-code-error"]').empty().hide();
      $('[data-drupal-selector="' + this.id + '-sort-code"]').removeClass('error').addClass('valid');
    },
    showAccountErrorMessage: function (message) {
      $('[data-drupal-selector="' + this.id + '-account-error').html(message).show();
      $('[data-drupal-selector="' + this.id + '-account').addClass('error').removeClass('valid');
    },
    showSortCodeErrorMessage: function (message) {
      $('[data-drupal-selector="' + this.id + '-sort-code-error"]').html(message).show();
      $('[data-drupal-selector="' + this.id + '-sort-code"]').addClass('error').removeClass('valid');
    },
    errorsExist: function () {
      return errorsExist(this.id);
    },
    // Show / Hide the Generic error message after the validation.
    BankAccountValidationShowErrorMessage: function (Error) {
      if (Error) {
        $('[data-drupal-selector="' + this.id + '--wrapper-error"]').html('Sorry, the bank details provided failed to pass our security checks. Please check the details and try again.').show();
        $('[data-drupal-selector="' + this.id + '-sort-code"]').addClass('error').removeClass('valid');
        $('[data-drupal-selector="' + this.id + '-account"]').addClass('error').removeClass('valid');
      }
      else {
        $('[data-drupal-selector="' + this.id + '--wrapper-error"]').empty().hide();
      }
    },
    // Provide some client side validation checks.
    CheckBankAccount: function (AccountNumber) {
      this.clearAccountErrorMessage();

      let error = false;

      let str = AccountNumber.replace(new RegExp("_", 'g'), "")
      if (!str.match(/^[0-9]*$/g)) {
        error = Drupal.t('Account number must contain only numbers.');
      }
      else if (str.length > 8) {
        error = Drupal.t('Account number need to be 8 digits long.');
      }
      else if (str.length < 8) {
        error = Drupal.t('Account number need to be 8 digits long. Please check if you need to add zeros in front of the account number.');
      }

      if (error !== false) {
        this.showAccountErrorMessage(error);
        return false;
      }
      else {
        this.showAccountValid();
        return true;
      }
    },
    CheckSortCode: function (SortCode) {
      this.clearSortCodeErrorMessage();

      if (SortCode.length <= 0) {
        return true;
      }

      let error = false;

      let str = SortCode.replace(new RegExp("-", 'g'), "").replace(new RegExp("_", 'g'), "");
      if (!str.match(/^[0-9]*$/g)) {
        error = Drupal.t('Sort code must only contain numbers.');
      }

      // Reject if the string is 6 or longer.
      if (str.length > 6) {
        error = Drupal.t('Sort code is too long. it should be 6 digits only.');
      }
      if (str.length < 6) {
        error = Drupal.t('Sort code is too short. it should be 6 digits only.');
      }

      if (error !== false) {
        this.showSortCodeErrorMessage(error);
        return false;
      }
      else {
        this.showSortCodeValid();
        return true;
      }
    },
    // Validate the back account number and then do a back end validation if all is ok.
    BankAccountLostFocus: function (e) {
      let account_number = $(e.target).val();
      if (account_number === '' || this.bankAccount === account_number) {
        // There is no difference in back account, or it doesn't exist, so don't validate again.
        e.stopPropagation();
        return;
      }

      this.bankAccount = account_number;

      if (!this.CheckBankAccount(account_number)) {
        e.preventDefault();
        e.stopPropagation();
        return;
      }

      if (account_number === '') {
        return;
      }

      if (this.sortCode === '') {
        // There is no sort code so can't validate.
        return;
      }
      // At this point sort code and bank account passed initial validation.
      // Now we validate the bank details.
      if (this.CheckBankAccount(this.bankAccount) && this.CheckSortCode(this.sortCode) && drupalSettings.webformBankAccount['active']) {
        this.BankAccountValidation_Interactive_Validate_v2_00(this.pcaApiKey, this.bankAccount, this.sortCode);
      }
    },
    // Validate the user Sort Code and if Bank Account present and ok then validate.
    SortCodeLostFocus: function (e) {
      let sort_code = $(e.target).val();

      if (this.sortCode === sort_code) {
        // Sort code is the same so no need to validate.
        e.stopPropagation();
        return;
      }

      this.sortCode = sort_code;

      if (!this.CheckSortCode(sort_code)) {
        e.preventDefault();
        e.stopPropagation();
        return;
      }

      if (sort_code === '') {
        // No value so don't validate.
        return;
      }

      if (this.bankAccount === '') {
        // No bank account number.
        return;
      }
      // At this point sort code and bank account passed initial validation.
      // Now we validate the bank details.
      if (this.CheckSortCode(this.sortCode) && this.CheckBankAccount(this.bankAccount) && drupalSettings.webformBankAccount['active']) {
        this.BankAccountValidation_Interactive_Validate_v2_00(this.pcaApiKey, this.bankAccount, this.sortCode);
      }
    },
    // Validate the bank account and sort code.
    BankAccountValidation_Interactive_Validate_v2_00: function (Key, AccountNumber, SortCode) {
      this.ajaxrunning = true;
      $.getJSON("https://services.postcodeanywhere.co.uk/BankAccountValidation/Interactive/Validate/v2.00/json3.ws?callback=?",
        {
          Key: Key,
          AccountNumber: AccountNumber,
          SortCode: SortCode
        },
        (function (thisouter) {
          return function (data) {
            thisouter.ajaxrunning = false;
            console.log(data);
            // Test for an error.
            if (data.Items.length === 1 && typeof(data.Items[0].Error) !== "undefined") {
              // Show the error message.
              alert(data.Items[0].Description);
              console.log(data);
            }
            else {
              // Check if there were any items found.
              if (data.Items.length === 0) {
                alert(Drupal.t("Sorry, there were no results"));
                console.log(data);
              }
              else {
                // FYI: The output is a JS object (e.g. data.Items[0].IsCorrect), the keys being:
                // IsCorrect.
                // IsDirectDebitCapable.
                // StatusInformation.
                // CorrectedSortCode.
                // CorrectedAccountNumber.
                // IBAN.
                // Bank.
                // BankBIC.
                // Branch.
                // BranchBIC.
                // ContactAddressLine1.
                // ContactAddressLine2.
                // ContactPostTown.
                // ContactPostcode.
                // ContactPhone.
                // ContactFax.
                // FasterPaymentsSupported.
                // CHAPSSupported.
                if (data.Items[0].IsCorrect) {
                  // Bank account details are valid.
                  thisouter.BankAccountValidationShowErrorMessage('', thisouter);
                  console.log(data);
                }
                else {
                  // There is something wrong with the bank account details.
                  thisouter.BankAccountValidationShowErrorMessage('Sorry, the bank details provided failed to pass our security checks. Please check the details and try again.', thisouter);
                  console.log(data);
                }
              }
            }
          }
        }(this)));
    }

  });

  // On AJAX forms, prevent submission if there are errors
  Drupal.behaviors.webformBankAccountAjaxSubmission = {
    attach: function (context) {
      if (typeof Drupal.Ajax !== 'undefined' && typeof Drupal.Ajax.prototype.beforeSubmitWebformBankAccount === 'undefined') {
        Drupal.Ajax.prototype.beforeSubmitWebformBankAccount = Drupal.Ajax.prototype.beforeSubmit;
        const id = drupalSettings.webformBankAccount['id'].replace(/--.[a-zA-Z0-9_-]{0,10}$/, "");

        Drupal.Ajax.prototype.beforeSubmit = function (form_values, element_settings, options) {
          if (this.element.classList.contains('webform-button--submit')) {
            // If errors are still shown, stop submission
            if(errorsExist(id)) {
              this.ajaxing = false;
              return false;
            }
          }
          return this.beforeSubmitWebformBankAccount.apply(this, arguments);
        };
      }
    }
  }

})(jQuery, Drupal, drupalSettings, Backbone);
