(function(Drupal) {

  Drupal.behaviors.donationBanner = {
    attach(context) {
      function toggleBanner(banner) {
        banner.classList.toggle("banner--active");

        const wrapper = banner.parentNode;
        wrapper.classList.toggle("container-closed");

        const panel = banner.nextElementSibling;

        if (banner.classList.contains("banner--active")) {
          banner.setAttribute("aria-expanded", "true");
          panel.setAttribute("aria-hidden", "false");
        } else {
          banner.setAttribute("aria-expanded", "false");
          panel.setAttribute("aria-hidden", "true");
        }
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
