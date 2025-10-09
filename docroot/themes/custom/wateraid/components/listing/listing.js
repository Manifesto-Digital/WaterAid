(function ($, Drupal) {
  Drupal.behaviors.listing = {
    attach() {
      function toggleFilter(legend) {
        legend.classList.toggle("listing__filter--open");
        const content = legend.nextElementSibling;

        if (legend.classList.contains("listing__filter--open")) {
          legend.setAttribute("aria-expanded", "true");
          content.setAttribute("aria-hidden", "false");
        } else {
          legend.setAttribute("aria-expanded", "false");
          content.setAttribute("aria-hidden", "true");
        }

        if (content && content.style.maxHeight) {
          content.style.maxHeight = null;
        } else {
          content.style.maxHeight = `${content.scrollHeight}px`;
        }
      }

      function attachEventListeners(listing) {
        const showButton = listing.querySelector("#show-filters");
        const hideButton = listing.querySelector("#hide-filters");

        const wrapper = listing.querySelector(".listing__wrapper");


        showButton.addEventListener("click", function () {
          wrapper.classList.toggle("listing--filters-open");
        });

        hideButton.addEventListener("click", function () {
          wrapper.classList.toggle("listing--filters-open");
        });

        const legends = listing.querySelectorAll(".listing__filters legend");
        legends.forEach((legend) => {
          legend.addEventListener("click", () => {
            toggleFilter(legend);
          });

          legend.addEventListener("keydown", function (event) {
            if (event.key === "Enter" || event.key === " ") {
              event.preventDefault();
              toggleFilter(legend);
            }
          });
        });
      }

      document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".listing").forEach((listing) => {
          attachEventListeners(listing);
        });
      });

      $(document).ajaxComplete(function (event, xhr, settings) {
        if (typeof settings.extraData !== 'undefined' && settings.extraData.hasOwnProperty('view_display_id')) {
          document.querySelectorAll(".listing").forEach((listing) => {
            const wrapper = listing.querySelector(".listing__wrapper");
            wrapper.classList.add("listing--filters-open");
            attachEventListeners(listing);
          });
        }
      });
    }
  };
})(jQuery, Drupal);
