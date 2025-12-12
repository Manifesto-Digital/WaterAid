(function ($, Drupal, once) {
      Drupal.behaviors.listing = {
        attach(context) {

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

            if (!showButton || !hideButton || !wrapper) {
              return;
            }

            showButton.addEventListener("click", function () {
              wrapper.classList.toggle("listing--filters-open");
            });

            hideButton.addEventListener("click", function () {
              wrapper.classList.toggle("listing--filters-open");
            });

            const legends = listing.querySelectorAll(".listing__filters legend");
            legends.forEach((legend) => {
              legend.setAttribute('tabindex', 0);
              legend.setAttribute('role', 'button');

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

          setTimeout(function() {
            // Use once() to ensure initialization happens only once per element
            once('listing-init', '.listing[data-component-id="wateraid:listing"]', context).forEach((listing) => {
              attachEventListeners(listing);
            });
          }, 300);

          $(document).ajaxComplete(function (event, xhr, settings) {
            if (typeof settings.extraData !== 'undefined' && settings.extraData.hasOwnProperty('view_display_id')) {
              once('listing-ajax', '.listing[data-component-id="wateraid:listing"]', context).forEach((listing) => {
                const wrapper = listing.querySelector(".listing__wrapper");

                if (wrapper) {
                  wrapper.classList.add("listing--filters-open");
                }
              });
            }
          });
        }
      };
})(jQuery, Drupal, once);

