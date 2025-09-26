(function (Drupal) {
  Drupal.behaviors.cta = {
    attach(context) {
      function addCustomVideoPlay(video) {
        const videoElement = video.querySelector("video");
        const playButton = video.querySelector(".cta__video--play");
        const pauseButton = video.querySelector(".cta__video--pause");

        if (playButton) {
          pauseButton.style.display = "none";
          videoElement.controls = false;

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

      context
        .querySelectorAll('.cta__video')
        .forEach((video) => {
          addCustomVideoPlay(video);
        });
    },
  };
})(Drupal);
