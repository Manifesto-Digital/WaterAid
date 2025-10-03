(function (Drupal) {
  Drupal.behaviors.video = {
    attach(context) {
      function addCustomVideoPlay(video) {
        const videoElement = video.querySelector("video");
        const playButton = video.querySelector(".video__play");
        const iframe = video.querySelector("iframe");

        if (iframe && playButton) {
          playButton.addEventListener("click", function () {
            playButton.style.display = "none";
            video.classList.add("playing");
            iframe.style.visibility = "visible";
          });
        }

        if (videoElement) {
          videoElement.controls = false;

          playButton.addEventListener("click", function () {
            videoElement.controls = true;
            videoElement.play();
            playButton.style.display = "none";
            video.classList.add("playing");
          });

          if(videoElement.paused){
            videoElement.addEventListener("click", function () {
              videoElement.controls = false;
              videoElement.pause();
              playButton.style.display = "block";
              video.classList.remove("playing");
            });
          }
        }
      }

      context.querySelectorAll(".video").forEach((video) => {
        addCustomVideoPlay(video);
      });
    },
  };
})(Drupal);
