(function (Drupal) {
  Drupal.behaviors.textOverMedia = {
    attach(context) {
      function addCustomVideoPlay(video) {
        const videoElement = video.querySelector("video");
        const playButton = video.querySelector(".text-over-media__video--play");
        const pauseButton = video.querySelector(".text-over-media__video--pause");

        if (videoElement) {
          videoElement.controls = false;
          pauseButton.style.display = "none";

          playButton.addEventListener("click", function () {
            videoElement.play();
            playButton.style.display = "none";
            pauseButton.style.display = "block";
          });

          pauseButton.addEventListener("click", function () {
            videoElement.pause();
            playButton.style.display = "block";
            pauseButton.style.display = "none";
          });
        }
      }

      function toggleImageCaption(image) {
        let openButton = image.querySelector(".image__button--open button");
        let closeButton = image.querySelector(".image__button--close button");

        if (openButton) {
          openButton.addEventListener("click", function () {
            image.classList.add("open");
          });
          closeButton.addEventListener("click", function () {
            image.classList.remove("open");
          });
        }
      }

      context.querySelectorAll(".text-over-media--image").forEach((image) => {
        toggleImageCaption(image);
      });

      context.querySelectorAll(".text-over-media__video").forEach((video) => {
        addCustomVideoPlay(video);
      });
    },
  };
})(Drupal);
