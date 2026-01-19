(function (Drupal) {
  Drupal.behaviors.parallaxScroll = {
    attach: function (context) {
      let width = document.body.clientWidth;
      const setScrollLogic = (component) => {
        const images = component.querySelectorAll(".parallax-scroll__image");

        // Set first image as active.
        if (images[0]) {
          images[0].classList.add("active");
        }

        // Set array of percentages based on how many images are uploaded.
        const imageCount = component.getAttribute("data-image-count");
        const changeDecimal = 1 / imageCount;
        const thresholdArray = [0];

        images.forEach((image) => {
          thresholdArray.push(
            image.getAttribute("data-image-no") * changeDecimal,
          );
        });

        // Change image when between thresholds.
        window.addEventListener("scroll", () => {
          const rect = component.getBoundingClientRect();
          const componentHeight = rect.height;

          const scrolledAmount = -rect.top;

          const scrollPercent = scrolledAmount / componentHeight;

          for (let i = 0; i < thresholdArray.length; i++) {
            if (
              scrollPercent >= thresholdArray[i] &&
              scrollPercent < thresholdArray[i + 1]
            ) {
              images.forEach((image) => {
                image.classList.remove("active");
              });
              images[i].classList.add("active");
            }
          }
        });
      };

      context.querySelectorAll(".parallax-scroll").forEach((parallaxScroll) => {
        setScrollLogic(parallaxScroll);
      });

      // Set image container height on desktop for sticky image on two column variant.
      const setScrollHeight = () => {
        const twoColumns = document.querySelectorAll(
          ".parallax-scroll--two-column",
        );
        const background = document.querySelectorAll(
          ".parallax-scroll--background",
        );
        let width = document.body.clientWidth;

        if (twoColumns && width > 1024) {
          twoColumns.forEach((component) => {
            const sectionImages = component.querySelectorAll(
              ".parallax-scroll__image",
            );

            sectionImages.forEach((image) => {
              image.style.height = `${component.offsetHeight}px`;
            });
          });
        }

        if (background && width > 1024) {
          background.forEach((component) => {
            const imagesWrappers =
              component.querySelectorAll(".image__wrapper");

            imagesWrappers.forEach((wrapper) => {
              wrapper.style.height = `${component.offsetHeight}px`;
            });
            const imageContents = component.querySelectorAll(".image__content");

            imageContents.forEach((content) => {
              content.style.height = `${component.offsetHeight}px`;
            });
          });
        }
      };

      if (width > 1024) {
        setScrollHeight();
      }

      window.addEventListener("resize", setScrollHeight);

      // Set image container height on mobile for sticky image.
      const setImageHeight = (component) => {
        width = document.body.clientWidth;
        let heights = [];

        if (width < 1024) {
          const sectionImages = component.querySelectorAll(".scroll-image");

          sectionImages.forEach((image) => {
            heights.push(image.offsetHeight);
          });

          const imageContainer = component.querySelector(".parallax-scroll__images");
          console.log(imageContainer);
          imageContainer.style.height = `${Math.min.apply(0, heights)}px`;
        }
      };


      context.querySelectorAll(".parallax-scroll").forEach((component) => {
        if (width < 1024) {
          setTimeout(() => {
            setImageHeight(component);
          }, 400);
        }

        window.addEventListener("resize", function(){
          setTimeout(() => {
            setImageHeight(component);
          }, 400);
        });
      });
    },
  };
})(Drupal);
