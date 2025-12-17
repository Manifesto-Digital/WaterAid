(function (Drupal) {
  Drupal.behaviors.hero = {
    attach(context) {
      const video = context.querySelector(".hero__video video");
      const playButton = context.querySelector(".hero__video--play");
      const pauseButton = context.querySelector(".hero__video--pause");
      let width = document.body.clientWidth;

      if (video) {
        pauseButton.style.display = "none";
        video.controls = false;

        playButton.addEventListener("click", function () {
          video.play();
          playButton.style.display = "none";
          pauseButton.style.display = "block";
        });

        pauseButton.addEventListener("click", function () {
          video.pause();
          playButton.style.display = "block";
          pauseButton.style.display = "none";
        });
      }

      const setHeroMargin = (hero, isDesktop) => {
        const heroHeight = hero.offsetHeight;
        const donateHeight = context.querySelector(".hero__donate").offsetHeight;
        let calculatedMargin = 0;

        if (heroHeight > 720 && isDesktop && (donateHeight + 96) > heroHeight) {
          calculatedMargin = donateHeight - heroHeight + 156;
        }

        hero.style.marginBottom = calculatedMargin + "px";
      };

      const onResize = function() {
        width = document.body.clientWidth;
        context.querySelectorAll('.hero--donate').forEach((hero) => {
        if (width > 1024) {
          setHeroMargin(hero, true);
        } else {
          setHeroMargin(hero, false);
        }
        });
      };
      window.addEventListener("resize", onResize);

      const radios = context.querySelectorAll('.hero__donate input[type="radio"]');
      radios.forEach((radio) => {
        radio.addEventListener("change", () => {
          setTimeout(onResize, 200);
        });
      });
      setTimeout(onResize, 500);

    },
  };
})(Drupal);
