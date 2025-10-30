(function ($, Drupal) {
  Drupal.behaviors.modal = {
    attach(context) {
      function openModal() {
        context
          .querySelectorAll('[data-component-id="wateraid:modal"]')
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
        const closeButton = modal.querySelector(".modal__close button");

        closeButton.addEventListener("click", function () {
          modal.classList.remove("open");
        });

        document.querySelector("body").classList.remove("modal-open");
      }

      const exitEventListener = (item) => {
        return function (event) {
          event.preventDefault();
          openModal();
          document.querySelectorAll("a").forEach((link) => {
            link.removeEventListener("click", exitEventListener);
          });
          const modal = context.querySelector(
            '[data-component-id="wateraid:modal"]',
          );
          const closeButton = modal.querySelector(".modal__close button");
          closeButton.addEventListener("click", function () {
            window.location = item.href;
          });
        };
      };

      context
        .querySelectorAll('[data-component-id="wateraid:modal"]')
        .forEach((modal) => {
          closeModal(modal);
        });

      document.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", exitEventListener(link));
      });

      setupTimers();
    },
  };
})(jQuery, Drupal);
