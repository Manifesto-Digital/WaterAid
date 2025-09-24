(function (Drupal) {
  Drupal.behaviors.image = {
    attach(context) {
      const openButton = context.getElementById("image-caption-open");
      const closeButton = context.getElementById("image-caption-close");
      const image = context.querySelector(".image__wrapper");

      openButton.addEventListener("click", toggleCaption);
      closeButton.addEventListener("click", toggleCaption);

      function toggleCaption() {
        image.classList.toggle("open");
      }
    },
  };
})(Drupal);
