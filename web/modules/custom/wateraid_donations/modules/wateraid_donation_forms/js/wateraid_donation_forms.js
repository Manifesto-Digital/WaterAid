/**
 * @file
 * Javascript behaviors for donation webform elements.
 */

(function ($, Drupal, drupalSettings, Backbone, once) {

  'use strict';

  // Merge strings on top of drupalSettings so that they are not mutable.
  let options = $.extend(drupalSettings.wateraidDonationForms, {
    strings: {
      open: Drupal.t('Open'),
      close: Drupal.t('Close')
    }
  });

  function isIE() {
    let ua = window.navigator.userAgent;
    let msie = ua.indexOf("MSIE ");

    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))  // If Internet Explorer, return version number
    {
      return true;
    }
    else  // If another browser, return 0
    {
      return false;
    }
  }

  /**
   * Namespace for wateraidDonationForms related functionality.
   *
   * @namespace
   */
  Drupal.wateraidDonationForms = {};

  /**
   * Backbone State Model.
   */
  Drupal.wateraidDonationForms.StateModel = Backbone.Model.extend({
    defaults: {
      'amount': 0,
      'currency': 'GBP',
      'countryCode': 'GB',
      'frequency': '',
      'fixedDuration': 0,
      'fixedPrice': '',
      'paymentMethod': '',
      'paymentStatus': '',
      'paymentResult': '',
      'disableCreatePaymentMethod': false,
      'step': 0
    },
    initialize: function (params) {
      alert('hello');
      let amount_defaults = params.amount_defaults;
      let frequency = amount_defaults.frequency_default;
      let default_frequency_settings = amount_defaults[frequency];
      this.set('amounts', params.amounts);
      this.typeSettings = [];
      let model = this;

      // Setting currency and country.
      model['attributes']['currency'] = drupalSettings.wateraidDonationForms.currency;
      model['attributes']['countryCode'] = drupalSettings.wateraidDonationForms.country;

      // Get the default settings for each type.
      $.each(params.amounts, function (index, value) {
        model.typeSettings[index] = [];
        let typeSettings = model.typeSettings[index];
        typeSettings['paymentMethod'] = amount_defaults[index]['default_payment_method'];
        typeSettings['amount'] = amount_defaults[index]['default_amount'];
      });
      this.setFrequency(frequency);
      this.setAmount(default_frequency_settings.default_amount);
      this.setDuration(default_frequency_settings.default_duration)
      if (params.amounts[frequency]['amounts'][default_frequency_settings.default_amount]) {
        this.setFixedPrice(params.amounts[frequency]['amounts'][default_frequency_settings.default_amount].stripePriceCode ?? '');
      }
      this.setPaymentMethod(default_frequency_settings.default_payment_method);
    },
    setAmount: function (amount) {
      let frequency = this.get('frequency');
      // Amount not formatted as number, to show benefit.
      // amount = Math.round(amount * 100) / 100;
      amount = amount.trim();
      this.typeSettings[frequency]['amount'] = amount;
      this.set('amount', amount);
    },
    setFrequency: function (frequency) {
      this.set('frequency', frequency);
      // var amount = this.typeSettings[frequency]['amount'];
      // Restore settings for frequency.
      this.set('amount', this.typeSettings[frequency]['amount']);
      this.set('paymentMethod', this.typeSettings[frequency]['paymentMethod']);
      // var paymentMethods = amounts[frequency].paymentMethods;
    },
    setDuration: function (duration) {
      this.set('fixedDuration', duration);
    },
    setFixedPrice: function (stripePriceCode) {
      this.set('fixedPrice', stripePriceCode);
    },
    setPaymentMethod: function (paymentMethod) {
      let frequency = this.get('frequency');
      this.typeSettings[frequency]['paymentMethod'] = paymentMethod;
      this.set('paymentMethod', paymentMethod);
    },
    setQuizId: function (quiz_id) {
      this.set('quizId', quiz_id)
    },
    getPaymentMethod: function (frequency) {
      return this.frequencySettings[frequency]['selectedPaymentMethod'];
    },
    setDisableCreatePaymentMethod: function (bool) {
      this.set('disableCreatePaymentMethod', bool);
    },
    getDisableCreatePaymentMethod: function () {
      return this.get('disableCreatePaymentMethod');
    },
    // Notify that payment has been completed client-side.
    paymentComplete: function (payment) {
      this.set('paymentResult', payment);
      // Post the current form.
      this.trigger('paymentComplete', this, payment);
    },
    // Notify that payment token has been created.
    paymentTokenCreated: function (token) {
      this.set('paymentResult', token);
    },
    refresh: function () {
      this.trigger('refresh', this);
    },
    getQueryParams: function () {
      let queryValue = {};
      $.each(document.location.search.substr(1).split('&'), function (c, q) {
        let i = q.split('=');
        if (i[0] && i[1]) {
          queryValue[i[0].toString()] = i[1].toString();
        }
      });
      if (queryValue.quiz_id) {
        this.setQuizId(queryValue.quiz_id);
      }
      if ((queryValue.fq && queryValue.val) || queryValue.quiz_id) {
        return queryValue;
      }
      else {
        return 0;
      }
    },
    isCurrentPaymentMethod: function (paymentView) {
      return paymentView.paymentMethod === this.get('paymentMethod');
    }
  }, Backbone.Events);

  /**
   * Backbone Form View.
   */
  Drupal.wateraidDonationForms.FormView = Backbone.View.extend({
    initialize: function () {
      this.listenTo(this.model, 'paymentComplete', this.doSubmitForm);
    },
    events: {
      "click .webform-button--previous": "backStep",
      "click .webform-button--next": "nextStep",
      "click .webform-button--submit": "submitForm",
      "click .payment-button": "submitForm"
    },
    backStep: function (event) {
      this.model.trigger('backStep', event);
    },
    nextStep: function (event) {
      let $form = $(this.el).closest('form');
      this.scrollToError(event);
      if ($form.hasClass('wateraid-donations-is-cta-form')) {
        if ($form.valid()) {
          event.preventDefault();
          let action = $form.attr('action');
          let freq = this.model.get('frequency');
          let value = this.model.get('amount');
          let duration = this.model.get('fixedDuration');

          let querys = action + '?fq=' + freq + '&val=' + value;
          if (duration) {
            querys += '&dur=' + duration;
          }

          $(location).attr('href', querys);
        }
        return false;
      }
      else {
        this.model.trigger('nextStep', event);
      }
    },
    submitForm: function (event) {
      // Fire off the DataLayer with completed submission.
      let donationFrequency = this.model.get('frequency');
      if (donationFrequency === 'one_off') {
        donationFrequency = 'one-off';
      }
      else {
        donationFrequency = 'regular';
      }
      dataLayer.push({
        'event': 'donateFormConfirmation',
        'gtm.formStep': 'Confirmation',
        'donationFrequency': donationFrequency,
        'model': this.model,
        'fullForm': this.$el.context
      });
      this.model.trigger('submitForm', event);
    },
    doSubmitForm: function () {
      $('input.webform-button--submit', this.el).click();
    },
    scrollToError: function (event) {
      let $form = $(this.el).closest('form');
      if ($form.data('validator') && !$form.valid()) {
        event.preventDefault();
        if ($('.error').length && $('.error:visible:first').length) {
          $('html, body').animate(
            { scrollTop: $('.error:visible:first').offset().top - 40 },
            'slow'
          );
        }
      }
    }
  });

  /**
   * Backbone Amount View.
   */
  Drupal.wateraidDonationForms.AmountView = Backbone.View.extend({
    initialize: function () {
      this.listenTo(this.model, 'change:frequency', this.render);
      this.listenTo(this.model, 'change:amount', this.render);
      this.listenTo(this.model, 'change:duration', this.render);
      // Check for URL params.
      let urlParams = this.model.getQueryParams();
      // Check amount fieldset to have form input.
      if (urlParams !== 0 && $(this.el).closest('form').hasClass('form-has-user-input') === false) {
        // Trigger click event on matched frequency obtained from the URL query param.
        $('fieldset.wa_donations_frequency input[value="' + urlParams.fq + '"]').prop('checked', true).click();
        // Try to match with a pre-defined value and if so trigger click event.
        let $value_element = $('.wa_donation_amounts-' + urlParams.fq + ' .webform-buttons-other input[value="' + urlParams.val + '"]');
        if ($value_element.length) {
          $value_element.prop('checked', true).click();
        }
        else {
          // Value doesn't exist, use "other" input.
          $('.wa_donation_amounts-' + urlParams.fq + ' .webform-buttons-other input[value="_other_"]').prop('checked', true).click();
          $('.wa_donation_amounts-' + urlParams.fq + ' .webform-buttons-other .js-webform-buttons-other-input input').val(urlParams.val);
        }
        // If there is a duration, trigger a click event
        if (urlParams.dur) {
          let $duration_element = $('.wa_donation_durations-' + urlParams.fq + ' .webform-buttons-other input[value="' + urlParams.dur + '"]');
          if ($duration_element.length) {
            $duration_element.prop('checked', true).click();
          }
        }
      }
      // Trigger events to setup initial state.
      $('fieldset.wa_donations_frequency input:checked').trigger('change');
      let frequency = this.model.get('frequency');
      $('.wa_donation_amounts-' + frequency + ' .webform-buttons-other input:checked').trigger('change');
      $('.wa_donation_amounts-' + frequency + ' .webform-buttons-other .js-webform-buttons-other-input input').trigger('input');
      $('.wa_donation_durations-' + frequency + ' .webform-buttons-other input:checked').trigger('change');
      this.render();
      if (this.model.get('quizId')) {
        let quizId = this.model.get('quizId');
        $('#edit-quiz-id').val(quizId);
      }
    },
    events: {
      "change fieldset.wa_donations_frequency": "handleFrequencyChange",
      "change .wa_donation_amounts_container .webform-buttons-other": "handleAmountChange",
      "change .wa_donation_durations-fixed_period": "handleDurationChange",
      "input .wa_donation_amounts_container .webform-buttons-other .js-webform-buttons-other-input": "handleAmountChange"
    },
    render: function () {
      // Only set the benefit if we are using the new donation picker.
      if ($(this.el).parents('.donation-cta-widget--old').length === 0) {
        this.setBenefit(this.model.get('frequency'), this.model.get('amount'));
      }
      // Show amounts for selected frequency, hide others.
      let amounts_container_selector = '.wa_donation_amounts-' + this.model.get('frequency');
      $(amounts_container_selector, this.el).show();
      $('.wa_donation_amounts_container', this.el).not(amounts_container_selector).hide();

      // Set required for 'other' inputs
      // Not compatible with v2 styling
      if ($(this.el).parents('.webform-style-v2').length === 0) {
        $(amounts_container_selector, this.el).find('.webform-buttons-other-input input').prop('required', true);
        $('.wa_donation_amounts_container', this.el).not(amounts_container_selector).find('.webform-buttons-other-input input').prop('required', false);
      }
    },
    handleFrequencyChange: function (event) {
      if (Drupal.blazy !== undefined) {
        Drupal.blazy.init.revalidate();
      }
      // Set frequency.
      if ($(event.target).val()) {
        this.model.setFrequency($(event.target).val());
      }
    },
    handleAmountChange: function (event) {
      // Set amount.
      if ($(event.target).val()) {
        this.model.setAmount($(event.target).val());

        // If it is a subscription, set the price code
        if ($(event.target).attr('data-stripe-price')) {
          this.model.setFixedPrice($(event.target).attr('data-stripe-price'));
        }
        else {
          this.model.setFixedPrice('');
        }
      }
    },
    handleDurationChange: function (event) {
      // Set duration.
      if ($(event.target).val()) {
        this.model.setDuration($(event.target).val());
      }
    },
    setBenefit: function (frequency, amount) {
      if (frequency !== undefined && amount !== undefined) {
        let benefit = '';
        if (drupalSettings.wateraidDonationForms.amounts[frequency].amounts[amount] !== undefined) {
          benefit = drupalSettings.wateraidDonationForms.amounts[frequency].amounts[amount].renderedBenefit;
        }
        $('.wa_donations_benefit', this.el).html(benefit);
        if (!benefit) {
          $('.wa_donations_benefit', this.el).addClass('empty');
        } else {
          $('.wa_donations_benefit', this.el).removeClass('empty');
        }
      }
    }
  });

  /**
   * Backbone Payment View.
   */
  Drupal.wateraidDonationForms.PaymentView = Backbone.View.extend({
    paymentMethodViews: {},
    initialize: function () {
      this.listenTo(this.model, 'change:paymentMethod', this.render);
      this.listenTo(this.model, 'change:frequency', this.setFrequency);
      this.listenTo(this.model, 'change:paymentResult', this.setPaymentResult);
      let model = this.model;
      let paymentMethodViews = this.paymentMethodViews;
      // Create views for all payment methods that declare views.
      $(".wa-donation-method[data-donations-view]", this.el).each(function () {
        let viewName = $(this).attr('data-donations-view');
        let paymentMethod = $(this).attr('data-donations-method');
        if (viewName !== undefined) {
          let view = new Drupal.wateraidDonationForms[viewName]($.extend({
            el: $(this),
            model: model
          }, options));
          view.paymentMethod = paymentMethod;
          paymentMethodViews[paymentMethod] = view;
        }
      });
      this.render();
    },
    events: {
      "change .wa-donation-method-selection": "changePaymentMethod"
    },
    setFrequency: function () {
      let frequency = this.model.get('frequency');
      $(".wa-donation-payment-selected-type").val(frequency);
      this.render();
    },
    render: function () {
      let currentFrequency = this.model.get('frequency');
      let currentPaymentMethod = this.model.get('paymentMethod');
      // Only show payment method options for selected frequency.
      $(".wa-donations-type[data-donations-type=" + currentFrequency + "]").show();
      $(".wa-donations-type").not("[data-donations-type=" + currentFrequency + "]").hide();
      // Hide any unavailable options.
      for (let paymentMethod in this.paymentMethodViews) {
        if (typeof this.paymentMethodViews[paymentMethod].isAvailable === 'function') {
          let id = $(".wa-donation-method-selection input:radio[value = " + paymentMethod + "]").prop('id');
          this.paymentMethodViews[paymentMethod].isAvailable(function (available) {
            if (available) {
              $("#".id).show();
              $("[for = " + id + "]").parent().show();
            }
            else {
              $("[for = " + id + "]").parent().remove();
            }
          })
        }
      }
      // Only show payment element for selected payment method.
      $(".wa-donation-method[data-donations-method=" + currentPaymentMethod + "]").show();
      $(".wa-donation-method").not("[data-donations-method=" + currentPaymentMethod + "]").hide();
    },
    changePaymentMethod: function () {
      let model = this.model;
      $(this.el).find('.wa-donation-method-selection :radio:checked').each(function () {
        model.setPaymentMethod($(this).val());
      });
    },
    setPaymentResult: function () {
      $('input.wa-donation-payment-result', this.el).val(this.model.get('paymentResult'));
    }
  });

  /**
   * Backbone Contact Details View.
   *
   * Captures user input into the State Model for Apple Pay.
   */
  Drupal.wateraidDonationForms.ContactDetailsView = Backbone.View.extend({
    events: {
      "change .wa-element-type-wateraid-forms-webform-name input": "setContactName",
      "change .wa-element-type-webform-address input": "setContactAddress",
      "change .wa-element-type-webform-address-sweden": "setContactAddressSweden",
      "change .wa-element-type-email": "setContactEmail",
      "change .wa-element-type-tel": "setContactPhone"
    },
    setContactName: function (event) {
      if (!this.model.get('contactName')) {
        this.model.set('contactName', {});
      }
      let matches = $(event.target).attr('name').match(/\[(.*?)\]/);
      this.model.get('contactName')[matches[1]] = $(event.target).val();
    },
    setContactAddress: function (event) {
      if (!this.model.get('contactAddress')) {
        this.model.set('contactAddress', {});
      }
      let matches = $(event.target).attr('name').match(/\[(.*?)\]/);
      this.model.get('contactAddress')[matches[1]] = $(event.target).val();
    },
    setContactAddressSweden: function (event) {
      if (!this.model.get('contactAddressSweden')) {
        this.model.set('contactAddressSweden', {});
      }
      let matches = $(event.target).attr('name').match(/\[(.*?)\]/);
      this.model.get('contactAddressSweden')[matches[1]] = $(event.target).val();
    },
    setContactEmail: function (event) {
      this.model.set('contactEmail', $(event.target).val());
    },
    setContactPhone: function (event) {
      this.model.set('contactPhone', $(event.target).val());
    }
  });

  /**
   * Initialise WaterAid donation forms.
   *
   * @param $form
   */
  function initWateraidDonationForms($form) {
    let wateraidDonationForms = Drupal.wateraidDonationForms;
    // Create a model and the appropriate views.
    let model = new wateraidDonationForms.StateModel({'amount_defaults': options.amount_defaults, 'amounts': options.amounts});
    // Bind to global window.
    Drupal.wateraidDonationForms.model = model;
    // Create payment element view.
    new wateraidDonationForms.FormView($.extend({el: $form, model: model}, options));
    // Create payment element view.
    new wateraidDonationForms.PaymentView($.extend({el: $form, model: model}, options));
    // Create details element view.
    new wateraidDonationForms.ContactDetailsView($.extend({el: $form, model: model}, options));
    // Iterate donation frequencies.
    $(once('wateraid_donations_forms', '.donations-webform-amount, .webform-type-donations-webform-amount', $form)).each(function (index) {
      // Create amount element views.
      new wateraidDonationForms.AmountView($.extend({el: $(this), model: model}, options));
    });
    model.refresh();
  }

  /**
   * Add behaviours for donation webform elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.wateraidDonationForms = {
    attach: function () {
      $(once('wateraid_donations_forms', 'form.wateraid-donations')).each(function () {
        initWateraidDonationForms(this);
      });
    }
  };

})(jQuery, Drupal, drupalSettings, Backbone, once);
