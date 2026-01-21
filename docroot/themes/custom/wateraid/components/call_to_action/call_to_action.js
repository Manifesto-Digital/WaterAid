(function (Drupal) {
  Drupal.behaviors.cta = {
    attach(context) {
      function addCustomVideoPlay(video) {
        const videoElement = video.querySelector("video");
        const playButton = video.querySelector(".cta__video--play");
        const pauseButton = video.querySelector(".cta__video--pause");

        if (videoElement) {
          pauseButton.style.display = "none";

          // iOS working to show thumbnail image
          const source = videoElement.querySelector('source');
          const src = source.getAttribute('src');

          source.setAttribute('src', `${src}#t=0.001`);
          if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
            videoElement.setAttribute('preload', 'metadata');
            videoElement.controls = true;
          } else {
            videoElement.controls = false;
          }

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


      context.querySelectorAll(".cta__video").forEach((video) => {
        addCustomVideoPlay(video);
      });
    },
  };
})(Drupal);
