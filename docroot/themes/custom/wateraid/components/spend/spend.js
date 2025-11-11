(function (Drupal) {
  Drupal.behaviors.spend = {
    attach(context) {

      function clickableSegment(spend) {
        const info1 = spend.querySelector('.spend__info.segment-1');
        const info2 = spend.querySelector('.spend__info.segment-2');
        const segment1 = spend.querySelector('.spend__segment.segment-1');
        const segment2 = spend.querySelector('.spend__segment.segment-2');

        segment1.addEventListener("click", () => {
          segment1.classList.add('active');
          segment2.classList.remove('active');
          info1.classList.add('active');
          info2.classList.remove('active');
        });
        segment2.addEventListener("click", () => {
          segment1.classList.remove('active');
          segment2.classList.add('active');
          info1.classList.remove('active');
          info2.classList.add('active');
        });
      }

      context.querySelectorAll('.spend').forEach((spend) => {
        clickableSegment(spend);
      });
    }
  };
})(Drupal);
