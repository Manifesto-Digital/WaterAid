(function (Drupal) {
  Drupal.behaviors.video = {
    attach(context) {
      function addCustomVideoPlay(video) {
        const videoElement = video.querySelector("video");
        const playButton = video.querySelector(".video__play");
        const iframe = video.querySelector("iframe");
        const thumbnail = video.querySelector(".video__thumbnail");

        if (iframe && playButton) {
          playButton.addEventListener("click", function (event) {
            event.stopPropagation();

            playButton.style.display = "none";
            video.classList.add("playing");
            iframe.style.visibility = "visible";

            if (thumbnail) {
              thumbnail.style.display = "none";
            }
          });
        }

        if (videoElement && playButton) {
          videoElement.controls = false;
          videoElement.playsInline = true;

          const source = videoElement.querySelector('source');
          const src = source.getAttribute('src');

          source.setAttribute('src', `${src}#t=0.001`);

          playButton.addEventListener("click", function (event) {
            event.stopPropagation();

            videoElement.controls = true;
            videoElement.play();
            playButton.style.display = "none";
            video.classList.add("playing");
            if (thumbnail) {
              thumbnail.style.display = "none";
            }
          });

          videoElement.addEventListener("click", function (event) {
            event.stopPropagation();
            videoElement.controls = false;
            videoElement.pause();
            playButton.style.display = "block";
            video.classList.remove("playing");
          });

          videoElement.addEventListener("ended", function () {
            playButton.style.display = "block";
            video.classList.remove("playing");
            videoElement.controls = false;
          });
        }
      }

      context.querySelectorAll(".video").forEach((video) => {
        addCustomVideoPlay(video);
      });
    },
  };
})(Drupal);
