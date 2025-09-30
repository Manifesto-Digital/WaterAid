(function (Drupal) {
  Drupal.behaviors.hero = {
    attach(context) {
      const video = context.querySelector("video");
      const playButton = context.querySelector(".hero__video--play");
      const pauseButton = context.querySelector(".hero__video--pause");

      pauseButton.style.display = 'none';
      video.controls = false;

      playButton.addEventListener("click", function() {
        video.play();
        playButton.style.display = 'none';
        pauseButton.style.display = 'block';
      });

      pauseButton.addEventListener("click", function() {
        video.pause();
        playButton.style.display = 'block';
        pauseButton.style.display = 'none';
      });
    },
  };
})(Drupal);
