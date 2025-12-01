(function (Drupal, once) {
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


        gallery.querySelectorAll("[data-controls='previous']").forEach((prev) => {
            prev.addEventListener("click", () => {
              slider.goTo("prev");
            });
          });
      }

      const elements = once('wateraid:image_gallery', '[data-component-id="wateraid:image_gallery"]', context);
      elements.forEach((gallery) => {
          initSlider(gallery);
        });
    },
  };
})(Drupal, once);
