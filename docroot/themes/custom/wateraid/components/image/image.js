(function (Drupal) {
  Drupal.behaviors.image = {
    attach(context) {
      function attachEventListeners(image) {
        let openButton = image.querySelector(".image__button--open button");
        let closeButton = image.querySelector(".image__button--close button");
        let wrapper = image.querySelector(".image__wrapper");
        
        if (openButton) {
          openButton.addEventListener("click", function () {
            wrapper.classList.toggle("open");
          });
          closeButton.addEventListener("click", function () {
            wrapper.classList.toggle("open");
          });
        }
      }

      context
        .querySelectorAll('[data-component-id="wateraid:image"]')
        .forEach((image) => {
          attachEventListeners(image);
        });
    },
  };
})(Drupal);
