/**
 * @file
 * Javascript behaviors for webform elements.
 */

(function ($, Drupal, drupalSettings, Backbone) {

  'use strict';

  /**
   * Add behaviours for donation webform elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformWizardSinglePage = {
    attach: function () {
      /*
       * When an "Edit" button is clicked within a step, trigger
       * the corresponding edit button in the actions section.
       */
      const button = $('[data-edit-step]');
      button.on('click', function (e) {
        e.preventDefault();
        const step = this.getAttribute('data-edit-step');
        document.querySelector("[data-trigger-step=" + step + "]").click();
      });

      // Check if status messages have been rendered inside a step.
      const step_messages = document.querySelector('.webform_step_status_messages');
      if (step_messages) {
        document.querySelectorAll('.status-messages').forEach((element) => {
          // Remove status message elements outside of the step.
          const parent = element.parentElement;
          if (!parent.classList.contains('webform_step_status_messages')) {
            element.remove();
          }
        });
      }
    }
  };

  /**
   * Add behaviour for scrolling to the active step of the single page form.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformWizardSinglePageScroll = {
    attach: function(context, settings) {
      // Do nothing unless the context element is the whole v2 form. This
      // prevents scrolling on the initial page load or reacting to unrelated
      // attach events.
      if ($(context).hasClass('webform-donations-page')) {
        let scrollToElement = $('[data-webform-single-page-scroll]', context);

        // Use once so that sub-elements of the form (e.g. switch address from
        // lookup to manual entry) don't trigger scrolling until a previous or
        // next section has activated. This is then cleared by the AJAX form
        // replacement so the same section can be scrolled to when needed.
        $(once('webform-single-page-scroll', scrollToElement, context)).each(() => {
          let fixedHeaderHeight = $('.layout-container > .header--color').height() ?? 0;
          let toolbarHeight = $('#toolbar-bar').height() ?? 0;
          // let toolbarDrawer = $('#toolbar-item-administration-tray');
          let toolbarDrawerHeight = $('#toolbar-item-administration-tray').height() ?? 0;
          // A buffer so that the scroll has some whitespace above the scrolled to element.
          let buffer = 40;

          $([document.documentElement, document.body]).animate({
            scrollTop: scrollToElement.offset().top - fixedHeaderHeight - toolbarHeight - toolbarDrawerHeight - buffer
          }, 1000);
        });
      }
    }
  };

  /**
   * Add behaviour to append " (optional)" to optional fields on donation form.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformWizardSinglePageOptionalLabels = {
    attach: function (context, settings) {
      // Append "(optional)" to every non-required label that isn't a checkbox.
      $('.webform-style-v2 .form-item label:not(.webform-checkbox, .option, .form-required, .button)').each((index, value) => {
        let label = $(value);
        let inputId = label.attr('for');
        if (inputId === undefined) {
          return;
        }
        let input = $('#' + inputId);
        // Also ignore any radio labels (but these don't have a consistent
        // class to exclude in the selector).
        if (input.attr('type') !== 'radio' && !input.hasClass('wa-element-type-donations-webform-amount-textfield')) {
          $(once('optional-labels', label)).each(() => {label.append(' ' + Drupal.t('(optional)'))});
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings, Backbone);
