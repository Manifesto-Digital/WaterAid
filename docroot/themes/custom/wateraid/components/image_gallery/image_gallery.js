(function (Drupal) {
  Drupal.behaviors.imageGallery = {
    attach(context) {
      function initSlider(gallery) {
        var slider = tns({
          container: gallery.querySelector(".image-gallery__slider"),
          items: 1,
          slideBy: "page",
          autoplay: false,
          controls: false,
          nav: false,
        });

        gallery.querySelectorAll("[data-controls='next']").forEach((next) => {
          next.addEventListener("click", () => {
            slider.goTo("next");
          });
        });

        gallery
          .querySelectorAll("[data-controls='previous']")
          .forEach((next) => {
            next.addEventListener("click", () => {
              slider.goTo("previous");
            });
          });
      }

      context
        .querySelectorAll('[data-component-id="wateraid:image_gallery"]')
        .forEach((gallery) => {
          initSlider(gallery);
        });
    },
  };
})(Drupal);
