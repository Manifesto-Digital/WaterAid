(function ($, Drupal, once) {
  Drupal.behaviors.inactivityModal = {
    attach(context) {

      let exitRedirectLink = document.querySelector('.site-header__container a').href || '/';

      function openModal() {
        context
          .querySelectorAll('[data-component-id="wateraid:inactivity_modal"]')
          .forEach((modal) => {
            modal.classList.add("open");
          });
        document.querySelector("body").classList.add("modal-open");
      }

      // After 60 seconds of inactivity open the modal.
      const timeoutInMilliseconds = 60000;
      let timeoutId;

      function startTimer() {
        timeoutId = window.setTimeout(openModal, timeoutInMilliseconds);
      }

      function resetTimer() {
        window.clearTimeout(timeoutId);
        startTimer();
      }

      // Reset timer when user is active.
      function setupTimers() {
        document.addEventListener("mousemove", resetTimer, false);
        document.addEventListener("mousedown", resetTimer, false);
        document.addEventListener("keypress", resetTimer, false);
        document.addEventListener("touchmove", resetTimer, false);

        startTimer();
      }

      // Close modal functionality.
      function closeModal(modal) {
        const closeButton = modal.querySelector(".inactivity-modal__close button");

        const continueDonation = modal.querySelector(
          "a.continue-donation-button",
        );
        if (continueDonation) {
          continueDonation.addEventListener("click", (e) => {
            e.preventDefault();
            modal.classList.remove("open");
            document.querySelector("body").classList.remove("modal-open");
          });
        }

        closeButton.addEventListener("click", function () {
          modal.classList.remove("open");
          document.querySelector("body").classList.remove("modal-open");

          // Redirect to clicked link.
          if (exitRedirectLink) {
            window.location = exitRedirectLink;
          }
        });

        document.querySelector("body").classList.remove("modal-open");
      }

      const exitEventListener = (item) => {
        return function (event) {
          event.preventDefault();

          // Check if the event was a click on a link and store the href
          const targetLink = event.target.closest('a');
          if (targetLink) {
            exitRedirectLink = targetLink.href;
          }

          openModal();
          document.querySelectorAll("a").forEach((link) => {
            link.removeEventListener("click", exitEventListener);
          });
          const modal = context.querySelector(
            '[data-component-id="wateraid:inactivity-modal"]',
          );
          if (modal) {
            const closeButton = modal.querySelector(".inactivity-modal__close button");
            closeButton.addEventListener("click", function () {
              // Use the clicked link href if available, otherwise fall back to the original item href
              window.location = clickedLinkHref || item.href;
            });
          }
        };
      };

      context
        .querySelectorAll('[data-component-id="wateraid:inactivity_modal"]')
        .forEach((modal) => {
          closeModal(modal);
        });

      if (document.body.classList.contains("user-logged-in") === false) {
        document
          .querySelectorAll("a:not(.continue-donation-button)")
          .forEach((link) => {
            link.addEventListener("click", exitEventListener(link));
          });
      }
      // Allow this modal to be closed externally.
      Drupal.inactivityModal = {
        closeModal: closeModal,
      };

      setupTimers();
    },
  };
})(jQuery, Drupal, once);
