(function (Drupal) {
  Drupal.behaviors.card = {
    attach(context) {
      function clickableCard(card) {
        const cardLink = card.querySelector('.card__link');
        const statsLink = card.querySelector('.card__link > .link');

        if (cardLink) {
          card.addEventListener("click", () => {
            card.focus();
            window.location.href = cardLink.href;
          });
        }
        if (statsLink) {
          card.addEventListener("click", () => {
            card.focus();
            window.location.href = statsLink.href;
          });
        }
      }

      context.querySelectorAll('[data-component="card"]').forEach((card) => {
        clickableCard(card);
      });
    },
  };
})(Drupal);
