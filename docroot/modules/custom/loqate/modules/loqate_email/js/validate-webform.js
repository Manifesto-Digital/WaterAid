/**
 * @file
 * Javascript behaviors for Loqate webform email elements.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  // Initialise timeout.
  let loqateKeyUpTimeout = null;

  // Milliseconds to wait before re-validating.
  let loqateKeyUpTimeoutMs = 1000;

  // Get original keyup function.
  let originalKeyUp = $.validator.defaults.onkeyup;

  // Override default jQuery validate onkeyup.
  let keyup = drupalSettings.cvJqueryValidateOptions.onkeyup = function(element, event) {
    if ($(element).hasData('loqate')) {
      // Element has the loqate option enabled.
      clearTimeout(loqateKeyUpTimeout);
      let thisInput = this;

      // Re-validate after user stops typing for a given delay.
      // This prevents synchronousRemote from jamming the user
      // input while the user is typing.
      loqateKeyUpTimeout = setTimeout(function() {
        $(element).valid();
        return thisInput.element( element );
      }, loqateKeyUpTimeoutMs)
    }
    else {
      return originalKeyUp.call(this, element, event);
    }
  };

  $.extend($.validator.defaults.onkeyup, keyup);

  /**
   * Attach validation to Loqate email elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.loqateEmailValidateWebform = {
    attach: function (context, settings) {
      // Create jQuery validate method. This is based on the remote validator
      // method (https://jqueryvalidation.org/remote-method/), which cannot
      // be used because it does not handle synchronous ajax calls correctly,
      // for example when a reCaptcha is present on the same form. For more
      // information, see https://stackoverflow.com/a/20750164.
      $.validator.addMethod("synchronousRemote", function (value, element, param) {
        if (this.optional(element)) {
          return "dependency-mismatch";
        }

        let previous = this.previousValue(element);
        if (!this.settings.messages[element.name]) {
          this.settings.messages[element.name] = {};
        }
        previous.originalMessage = this.settings.messages[element.name].remote;
        this.settings.messages[element.name].remote = previous.message;

        param = typeof param === "string" && { url: param } || param;

        if (previous.old === value) {
          return previous.valid;
        }

        previous.old = value;
        let validator = this;
        this.startRequest(element);
        let valid = "pending";
        const submit = $('input[type="submit"]');

        $.ajax($.extend(true, {
          url: param,
          mode: "abort",
          port: "validate" + element.name,
          dataType: "json",
          beforeSend: function() {
            // Show loading spinner.
            const message = Drupal.t('Please wait...');
            const throbber = $(Drupal.theme.ajaxProgressThrobber(message));
            throbber.insertAfter(element);
            submit.attr('disabled', 'disabled');
          },
          success: function (response, status, event) {
            validator.settings.messages[element.name].remote = previous.originalMessage;
            let valid = response.valid === true || response.valid === "true";
            let hashElement = this.hashElement;
            if (valid) {
              let submitted = validator.formSubmitted;
              validator.prepareElement(element);
              validator.formSubmitted = submitted;
              validator.successList.push(element);
              delete validator.invalid[element.name];

              // Update hash value.
              hashElement.val(response.hash);

              // Show errors.
              validator.showErrors();

            } else {
              let errors = {};
              let message = response.message || validator.defaultMessage(element, "remote");
              hashElement.val('');
              errors[element.name] = previous.message = $.isFunction(message) ? message(value) : message;
              validator.invalid[element.name] = true;
              validator.showErrors(errors);
            }
            previous.valid = valid;
            validator.stopRequest(element, valid);
          },
          complete: function () {
            // Remove loading spinner.
            const throbber = $('.ajax-progress-throbber');
            if (throbber.length) {
              throbber.remove();
            }
            submit.removeAttr('disabled');
          }
        }, param));
        return valid;
      }, drupalSettings.loqateEmail.errorMessage);

      let id = drupalSettings.loqateEmail.id;
      let enabled = drupalSettings.loqateEmail.enabled;
      let refuseDisposable = drupalSettings.loqateEmail.refuseDisposable;

      if (enabled === true) {
        // Add Loqate validation.
        $(context).find('#' + id).each(function() {
          const email = $(this).find('input[type="email"]');
          const hashElement = $(this).find('input[data-hash]');
          email.rules( 'add', {
            synchronousRemote: {
              url: drupalSettings.loqateEmail.endpointUrl,
              type: 'get',
              hashElement: hashElement,
              data: {
                email: function() {
                  return email.val();
                },
                refuseDisposable: refuseDisposable,
              },
            },
          });
          // Validate any pre-filled email fields.
          if (email.val()) {
            const form = email.closest('form');
            $('#' + form.attr('id')).validate().element('#' + email.attr('id'));
          }
        });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
