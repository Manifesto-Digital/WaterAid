((Drupal) => {
  'use strict';
  Drupal.behaviors.waWebformSubmission = {
    attach(context) {
      const webformSubmissionForm = context.querySelector(
        '.webform-submission-form'
      );
      if (!webformSubmissionForm) {
        return;
      }

      // Clear SubmitOnce classes on page show so that submit buttons are not disabled when using browser back button.
      window.addEventListener('pageshow', () => {
        if (Drupal.behaviors.webformSubmitOnce) {
          Drupal.behaviors.webformSubmitOnce.clear();
        }
      });

      // Clone back button and move it in DOM to be accessible in tabindex order.
      // Listener must be added before webform-loaded Event is triggered in
      // subsequent code.
      const backBtn = document.querySelector('.webform-button--previous');
      if (webformSubmissionForm && !!backBtn) {
        webformSubmissionForm.addEventListener('webform-loaded', () => {
          const clonedBtn = backBtn.cloneNode(true);
          clonedBtn.setAttribute('id', 'edit-actions-wizard-prev-clone');
          clonedBtn.addEventListener('click', () => {
            backBtn.click();
          });
          document.querySelector('.webform-progress').prepend(clonedBtn);
          backBtn.classList.add('hide');
        });
      }

      window.addEventListener('load', () => {
        // Create and dispatch a webform-loaded event.
        let webformEvent;
        if (typeof Event === 'function') {
          webformEvent = new Event('webform-loaded');
        } else {
          webformEvent = document.createEvent('Event');
          webformEvent.initEvent('webform-loaded', true, true);
        }
        webformSubmissionForm.dispatchEvent(webformEvent);

        // Show buttons panel in datepicker.
        if (Drupal.webform.datePicker) {
          Drupal.webform.datePicker.options = {
            showButtonPanel: true
          };
        }

        // Observe for form errors and add role alert attribute.
        const config = { attributes: true, childList: true, subtree: true };
        const callback = (mutationsList, observer) => {
          mutationsList.forEach(({ type, target }) => {
            if (
              (type === 'childList' || type === 'attributes') &&
              target.tagName === 'LABEL' &&
              target.classList.contains('error') &&
              !target.hasAttribute('role')
            ) {
              target.setAttribute('role', 'alert');
            }
          });
        };
        const observer = new MutationObserver(callback);
        observer.observe(webformSubmissionForm, config);
      });

      // Remove amount logo and attach after submit button
      const iconParentElement = document.querySelector('.webform-style-v2');
      let divIconToMove;
      if (iconParentElement) {
        divIconToMove = iconParentElement.querySelector('.field-name-field-b-pl-icon');
      }

      // Identify the target location in the footer
      const footerTarget = document.querySelector('.webform-actions.wa-element-type-webform-actions');

      // Check if both elements exist
      if (divIconToMove && footerTarget) {
          // Detach the div from its current location in the header
          divIconToMove.parentNode.removeChild(divIconToMove);

          // Append the detached div to the target location in the footer
          footerTarget.appendChild(divIconToMove);
      }
    }
  };
})(Drupal);
