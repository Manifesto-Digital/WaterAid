/**
 * @file
 * Javascript behaviors for webform elements.
 */

(function ($, Drupal, drupalSettings, Backbone, once) {

  'use strict';

  // Merge strings on top of drupalSettings so that they are not mutable.
  let options = $.extend(drupalSettings.webformWizardExtra,
    {
      strings: {
        open: Drupal.t('Open'),
        close: Drupal.t('Close')
      }
    }
  );

  /**
   * Namespace for webformWizardExtra related functionality.
   *
   * @namespace
   */
  Drupal.webformWizardExtra = {};

  /**
   * Wizard Extra State Model.
   */
  Drupal.webformWizardExtra.StateModel = Backbone.Model.extend({
    defaults: {
      'current_step': 0,
      'steps': []
    },
    initialize: function (params) {
      let current_step = params.current_step;
      let steps = params.steps;
      let model = this;
    },
    isLastStep: function () {
      let current_step = this.get('current_step');
      let steps = this.get('steps');

      return current_step === ($(steps).length - 1);
    },
    isFirstStep: function () {
      let current_step = this.get('current_step');
      return current_step === 0;
    },
    stepForward: function () {
      let current_step = this.get('current_step');
      current_step += 1;
      this.set('current_step', current_step);
    },
    stepBack: function () {
      let current_step = this.get('current_step');
      current_step -= 1;
      this.set('current_step', current_step);
    }
  }, Backbone.Events);

  /**
   * Wizard Extra Progress View.
   */
  Drupal.webformWizardExtra.ProgressView = Backbone.View.extend({
    initialize: function () {
      this.listenTo(this.model, 'change:current_step', this.refresh);
      this.refresh();
    },
    refresh: function () {
      let current_step = this.model.get('current_step');
      let steps = this.model.get('steps');

      let total_steps = $('.webform-progress-bar').attr('data-steps');

      // Set the status summary.
      let status = Drupal.t('Step') + ' ' + (current_step + 1) + ' ' + Drupal.t('of') + ' ' + total_steps;
      $('.webform-progress__summary', this.el).text(status);

      let queryParams = getQueryParams();
      if (queryParams !== 0 && queryParams.val) {
        $('.donation-amount-info').text(Drupal.t('You are donating £@amount', {
          '@amount' : Math.round(Drupal.wateraidDonationForms.model.get('amount') * 100) / 100
        }));
      }

      // Set the step title.
      let step_title = $('.webform-progress-bar__page', this.el).eq(current_step).text();
      let progressH1 = $('.webform-progress h1');
      progressH1.text(step_title);

      // Show/hide if class on wizard page - add hide_title as custom attribute.
      progressH1.toggle(!$(steps).eq(current_step).hasClass('webform-hide-title'));

      // Update the progress bar.
      $('.webform-progress-bar__page:gt(' + current_step + ')', this.el).removeClass('webform-progress-bar__page--current').removeClass('webform-progress-bar__page--done');
      $('.webform-progress-bar__page:lt(' + current_step + ')', this.el).removeClass('webform-progress-bar__page--current').addClass('webform-progress-bar__page--done');
      $('.webform-progress-bar__page', this.el).eq(current_step).removeClass('webform-progress-bar__page--done').addClass('webform-progress-bar__page--current');

      // Show/hide the back button.
      $('.webform-button--previous', this.el).toggle(!this.model.isFirstStep());
    }
  });

  /**
   * Wizard Extra Form View.
   */
  Drupal.webformWizardExtra.FormView = Backbone.View.extend({
    initialize: function () {
      this.listenTo(this.model, 'change:current_step', this.refresh);
      this.refresh();
    },
    events: {
      "click .webform-button--previous": "backStep",
      "click .webform-button--next": "nextStep"
    },
    backStep: function (event) {
      let current_step = this.model.get('current_step');

      // Refresh validation but don't prevent back step if invalid.
      // Only check validation if this step has already been validated.
      if ($('.webform-wizard-page:eq(' + current_step + ')', this.el).hasClass('wwp-validated')) {
        $('.webform-wizard-page:eq(' + current_step + ') input', this.el).valid();
      }
      event.preventDefault();
      this.model.stepBack();
    },
    nextStep: function (event) {
      if ($(this.el).closest('form').hasClass('wateraid-donations-is-cta-form')) {
        return;
      }
      let current_step = this.model.get('current_step');

      if (!this.model.isLastStep()) {
        event.preventDefault();
      }

      // Check validation and allow next if valid.
      if ($('.webform-wizard-page:eq(' + current_step + ') input', this.el).valid()) {
        let step_title = $('.webform-progress-bar__page', this.el).eq(current_step).text();
        step_title = step_title.replace(/(\r\n|\n|\r)/gm, "");
        step_title = step_title.trim();
        dataLayer.push({
          'event':'donateFormStepForward',
          'gtm.formStep': step_title,
          'model':this.model
        });
        this.model.stepForward();
      }

      // Mark as validated.
      $('.webform-wizard-page:eq(' + current_step + ')', this.el).addClass('wwp-validated');
    },
    refresh: function () {
      let current_step = this.model.get('current_step');
      let steps = this.model.get('steps');

      $('.webform-wizard-page:lt(' + current_step + ')', this.el).hide();
      $('.webform-wizard-page:gt(' + current_step + ')', this.el).hide();
      $('.webform-wizard-page', this.el).eq(current_step).show();

      if (this.model.isLastStep()) {
        $('.form-actions', this.el).show();
      }
      else {
        $('.form-actions', this.el).hide();
      }
      if ($(".webform-donations-page").length) {
        $('html, body').animate({
            scrollTop: $(this.el).offset().top - $('header').outerHeight()
        }, 200);
      }
    }
  });

  /**
   * Initialise Webform Wizard Extra.
   *
   * @param $form
   * @param html
   */
  function initWebformWizardExtra($form, html) {

    // Create a model and the appropriate views.
    let step = 0;
    if (drupalSettings.webformWizardExtra.startOnStep) {
      step = drupalSettings.webformWizardExtra.startOnStep;
    }
    else {
      let queryParams = getQueryParams();
      if (queryParams !== 0) {
        step = 1;
      }
    }

    if (drupalSettings.webformWizardExtra.payment_element_name) {
      // Get the element name from settings.
      let paymentElementName = drupalSettings.webformWizardExtra.payment_element_name;
      // Find the payment element.
      let $paymentElement = $('#edit-' + paymentElementName + '--wrapper');
      // Check for server side errors.
      if ($paymentElement.length && $paymentElement.hasClass('error')) {
        // If we do have such errors, then override the step index.
        step = $('.webform-wizard-page').index($paymentElement.parent('.webform-wizard-page'));
      }
    }

    let model = new Drupal.webformWizardExtra.StateModel({
      'current_step': step,
      'steps': $('.webform-wizard-page', $form)
    });

    let options = {};

    // Create payment element view.
    new Drupal.webformWizardExtra.FormView($.extend({el: $form, model: model}, options));

    new Drupal.webformWizardExtra.ProgressView($.extend({el: $('.webform-progress', $form), model: model}, options));
  }

  /**
   * Helper function.
   *
   * @returns {*}
   */
  function getQueryParams() {
    let queryValue = {};
    $.each(document.location.search.substr(1).split('&'), function (c, q) {
      let i = q.split('=');
      if (i[0] && i[1]) {
        queryValue[i[0].toString()] = i[1].toString();
      }
    });
    if (queryValue.fq && queryValue.val) {
      return queryValue;
    }
    else {
      return 0;
    }
  }

  /**
   * Add behaviours for donation webform elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformWizardExtra = {
    attach: function () {
      $(once('webform_wizard-extra', $('form.webform-submission-form').not('.wateraid-donations-is-cta-form'))).each(function () {
        initWebformWizardExtra(this);
      });
    }
  };

})(jQuery, Drupal, drupalSettings, Backbone, once);
