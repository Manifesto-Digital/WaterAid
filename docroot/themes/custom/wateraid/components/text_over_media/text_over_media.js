(function (Drupal) {
  Drupal.behaviors.textOverMedia = {
    attach(context) {
      let width = document.body.clientWidth;
      function addCustomVideoPlay(video) {
        const videoElement = video.querySelector("video");
        const playButton = video.querySelector(".text-over-media__video--play");
        const pauseButton = video.querySelector(".text-over-media__video--pause");

        if (videoElement) {
          pauseButton.style.display = "none";
          videoElement.playsInline = true;
          videoElement.muted = true;

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
        width = document.body.clientWidth;
        if (width > 1024) {
          // Add get content height.
          const content = component.querySelector(".text-over-media__content");
          const contentHeight = content.offsetHeight;

          // Add padding for component.
          const newComponentHeight = contentHeight + 96;
          component.style.height = `${newComponentHeight}px`;

          // Set image wrapper height for sticky caption.
          const imageWrapper = component.querySelector(".image__wrapper");
          const imageContent = component.querySelector(".image__content");
          if (imageContent && imageWrapper) {
            imageWrapper.style.height = `${component.offsetHeight}px`;
            imageContent.style.height = `${component.offsetHeight}px`;
          }

          // Set video wrapper height for sticky buttons.
          const videoWrapper = component.querySelector(".text-over-media__video");
          if (videoWrapper) {
            videoWrapper.style.height = `${component.offsetHeight}px`;
          }
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
