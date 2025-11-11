(function (Drupal) {
  Drupal.behaviors.spend = {
    attach(context) {

      function clickableSegment(spend) {
        const diagram1 = spend.querySelector('.diagram-1');
        const diagram2 = spend.querySelector('.diagram-2');
        const segment1 = diagram2.querySelector('.segment-1');
        const segment2 = diagram1.querySelector('.segment-2');

        segment1.addEventListener("click", () => {
          diagram1.classList.add('active');
          diagram2.classList.remove('active');
        });
        segment2.addEventListener("click", () => {
          diagram1.classList.remove('active');
          diagram2.classList.add('active');
        });
      }

      context.querySelectorAll('.spend').forEach((spend) => {
        clickableSegment(spend);
      });
    }
  };
})(Drupal);
