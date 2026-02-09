(function (Drupal) {
  Drupal.behaviors.button = {
    attach(context) {
      function hideButtonHover(button) {
        let buttonWrapper = button.parentElement.parentElement;
        buttonWrapper.style.display = 'none';
      }

      context.querySelectorAll("input.button--input-wrapped").forEach((button) => {

        setTimeout(() => {
          if (button.style.display === 'none') {
            hideButtonHover(button);
          }
        }, 200);

      });
    },
  };
})(Drupal);
