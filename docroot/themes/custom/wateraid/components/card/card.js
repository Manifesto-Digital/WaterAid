(function (Drupal) {
  Drupal.behaviors.card = {
    attach(context) {
      function clickableCard(card) {
        const cardLink = card.href;
        card.addEventListener("click", () => {
          card.focus();
          window.location.href = cardLink;
        });
      }

      context.querySelectorAll('[data-component="card"]').forEach((card) => {
        clickableCard(card);
      });
    },
  };
})(Drupal);
