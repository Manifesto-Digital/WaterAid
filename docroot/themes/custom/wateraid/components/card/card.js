(function (Drupal) {
  Drupal.behaviors.card = {
    attach(context) {
      function clickableCard(card) {
        const cardLink = card.querySelector('.card__link');
        const statsLink = card.querySelector('.card__link > .link');

        const isExternalURL = new URL(cardLink.href).origin !== location.origin;
        if (cardLink) {
          card.addEventListener("click", () => {
            card.focus();

            if (isExternalURL){
              window.open(cardLink.href);
            } else {
              window.location.href = cardLink.href;
            }
          });
        }
        if (statsLink) {
          card.addEventListener("click", () => {
            card.focus();
            if (isExternalURL){
              window.open(cardLink.href);
            } else {
              window.location.href = cardLink.href;
            }
          });
        }
      }

      context.querySelectorAll('[data-component="card"]').forEach((card) => {
        clickableCard(card);
      });
    },
  };
})(Drupal);
