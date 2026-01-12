(function (Drupal) {
  Drupal.behaviors.textOverMedia = {
    attach(context) {
      function addCustomVideoPlay(video) {
        const videoElement = video.querySelector("video");
        const playButton = video.querySelector(".text-over-media__video--play");
        const pauseButton = video.querySelector(".text-over-media__video--pause");

        if (videoElement) {
          pauseButton.style.display = "none";
          videoElement.controls = false;
          videoElement.muted = true;

          pauseButton.addEventListener("click", function () {
            videoElement.pause();
            playButton.style.display = "block";
            pauseButton.style.display = "none";
          });

          playButton.addEventListener("click", function () {
            videoElement.play();
            playButton.style.display = "none";
            pauseButton.style.display = "block";
          });

          if (width > 1024) {
            videoElement.autoplay = true;
            videoElement.play();
            pauseButton.style.display = "block";
            playButton.style.display = "none";
          }
        }
      }
      // Set component height on desktop.
      function setComponentHeight(component) {
        let width = document.body.clientWidth;
        if (width > 1024) {
          // Add get content height.
          const content = component.querySelector(".text-over-media__content");
          const contentHeight = content.offsetHeight;

          // Add padding for component.
          const newComponentHeight = contentHeight + 96;
          component.style.height = newComponentHeight + "px";
        }
        else {
          component.style.height = "unset";
        }
      }

      context.querySelectorAll(".text-over-media__wrapper").forEach((component) => {
        setTimeout(() => {
          setComponentHeight(component);
        }, 500);

        window.addEventListener("resize", function(){
          setTimeout(() => {
            setComponentHeight(component);
          }, 300);
        });
      });

      context.querySelectorAll(".text-over-media__video").forEach((video) => {
        addCustomVideoPlay(video);
      });
    },
  };
})(Drupal);
