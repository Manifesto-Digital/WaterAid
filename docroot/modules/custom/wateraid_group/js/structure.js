(function (Drupal) {
  'use strict';

  Drupal.behaviors.wateraidGroupStructure = {
    attach: function (context, settings) {

      // Hide the Layouts Paragraphs add buttons to stop people changing content
      // they can't save.
      const add = context.querySelectorAll('.lpb-btn--add');

      if (add.length > 0) {
        add.forEach((add) => {
          add.style.display = 'none';
        })
      }
    }
  };

})(Drupal);
