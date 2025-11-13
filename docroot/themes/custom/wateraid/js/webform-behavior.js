(function (Drupal, drupalSettings) {
  'use strict';


  const replaceSubmit = (findSubmit) => {
    const newElement = document.createElement('button');
    newElement.textContent = findSubmit.value;
    findSubmit.parentNode.replaceChild(newElement, findSubmit);

    newElement.addEventListener('click', (e) => {
      e.preventDefault();
      window.location.href = window.location.href.split('?')[0];
    });
  }

  Drupal.behaviors.wateraidWebformBehavior = {
    attach: function (context, settings) {
      let findSubmit = document.querySelector('.field--name-webform form fieldset:first-of-type input[type="submit"]');
      if (!findSubmit) {
        findSubmit = context.querySelector('.webform-submission-form form [data-edit-step="step_1"]');
      }

      if (findSubmit) {
        replaceSubmit(findSubmit);
      }
    }
  };

})(Drupal, drupalSettings);
