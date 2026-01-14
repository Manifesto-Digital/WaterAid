(function (Drupal) {
  Drupal.behaviors.image = {
    attach(context) {
      function attachEventListeners(image) {
        let openButton = image.querySelector(".image__button--open button");
        let closeButton = image.querySelector(".image__button--close button");
        let wrapper = image.querySelector(".image__wrapper");

        if (openButton) {
          openButton.addEventListener("click", function () {
            wrapper.classList.add("open");
          });
          closeButton.addEventListener("click", function () {
            wrapper.classList.remove("open");
          });
        }
      }

      // Set image focal point and transform.
      function focusImage(image) {
        const width = image.getAttribute('data-width');
        const height = image.getAttribute('data-height');
        const imageWidth = image.getAttribute('data-image-width');
        const imageHeight = image.getAttribute('data-image-height');

        const pointX = image.getAttribute('data-x');
        const pointY = image.getAttribute('data-y');

        const rotate = image.getAttribute('data-rotate');
        const scaleX = image.getAttribute('data-scale-x');
        const scaleY = image.getAttribute('data-scale-y');

        // Get focus point %.
        const focalPointX = Math.round(((width / 2) + Number(pointX)) / imageWidth * 100);
        const focalPointY = Math.round(((height / 2) + Number(pointY)) / imageHeight * 100);

        const img = image.querySelector('img');

        // Set the image styling, object position for focal point and transform properties.
        const styles = {
          objectPosition: `${focalPointX}% ${focalPointY}%`,
          transform: `rotate(${rotate}deg) scaleX(${scaleX}) scaleY(${scaleY})`,
        };

        Object.assign(img.style, styles);
      }

      context.querySelectorAll('.image--cropped').forEach((image) => {
        focusImage(image);
      });

      context
        .querySelectorAll('[data-component-id="wateraid:image"]')
        .forEach((image) => {
          attachEventListeners(image);
        });

    },
  };
})(Drupal);
