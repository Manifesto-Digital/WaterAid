(function (Drupal) {
  Drupal.behaviors.image = {
    attach(context) {
      let openButton = context.getElementById("image-caption-open");
      let closeButton = context.getElementById("image-caption-close");
      let image = context.querySelector(".image__wrapper");

      console.log(openButton);
      console.log(closeButton);

      openButton.addEventListener("click", toggleCaption);
      closeButton.addEventListener("click", toggleCaption);

      function toggleCaption() {
        image.classList.toggle("open");
      }
    },
  };
})(Drupal);
