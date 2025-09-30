(function(Drupal) {

  function toggleAccordion(accordion) {
    accordion.classList.toggle('accordion--active');
    const panel = accordion.nextElementSibling;

    if (accordion.classList.contains('accordion--active')) {
      accordion.setAttribute('aria-expanded', 'true');
      panel.setAttribute('aria-hidden', 'false');
    } else {
      accordion.setAttribute('aria-expanded', 'false');
      panel.setAttribute('aria-hidden', 'true');
    }

    if (panel.style.maxHeight) {
      panel.style.maxHeight = null;
    } else {
      panel.style.maxHeight = `${panel.scrollHeight}px`;
    }
  }

  function attachEventListeners(accordionElement) {
    const accordions = accordionElement.querySelectorAll('.accordion-item__title');
    accordions.forEach(accordion => {

      // Click event listener.
      accordion.addEventListener('click', () => {
        toggleAccordion(accordion);
      });

      // Keypress event listener.
      accordion.addEventListener("keydown", function (event) {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          toggleAccordion(accordion);
        }
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".accordion").forEach(accordion => {
      attachEventListeners(accordion);
    });
  });

})(Drupal);
