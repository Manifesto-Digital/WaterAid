(function(Drupal) {

  Drupal.behaviors.donationBanner = {
    attach(context) {
      function toggleBanner(banner) {
        const wrapper = banner.parentNode.parentNode;

        let scatoken = wrapper.dataset.token;

        fetch(Drupal.url('wateraid-donation-forms/reminder?token=' + scatoken), {
          method: "POST",
          body: '',
          headers: {
            "Content-type": "application/json; charset=UTF-8"
          }
        }).then(r => {
          if (r.status === 200) {
            wrapper.style.display = 'none';
          }
        });
      }

      function attachBannerEventListeners(bannerElement) {
        const banners = bannerElement.querySelectorAll(
          ".donation__toggle",
        );
        banners.forEach((banner) => {
          // Click event listener.
          banner.addEventListener("click", () => {
            toggleBanner(banner);
          });

          // Keypress event listener.
          banner.addEventListener("keydown", function (event) {
            if (event.key === "Enter" || event.key === " ") {
              event.preventDefault();
              toggleBanner(banner);
            }
          });
        });
      }

      document.querySelectorAll(".donation-banner").forEach((banner) => {
        attachBannerEventListeners(banner);
      });
    },
  };

})(Drupal);
