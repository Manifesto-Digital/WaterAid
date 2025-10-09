(function (Drupal) {
  function toggleAccordion(accordion) {
    accordion.classList.toggle("accordion--active");
    const panel = accordion.nextElementSibling;

    if (accordion.classList.contains("accordion--active")) {
      accordion.setAttribute("aria-expanded", "true");
      panel.setAttribute("aria-hidden", "false");
    } else {
      accordion.setAttribute("aria-expanded", "false");
      panel.setAttribute("aria-hidden", "true");
    }

    if (panel.style.maxHeight) {
      panel.style.maxHeight = null;
    } else {
      panel.style.maxHeight = `${panel.scrollHeight}px`;
    }
  }

  function attachEventListeners(accordionElement) {
    const accordions = accordionElement.querySelectorAll(
      ".accordion-item__title",
    );
    accordions.forEach((accordion) => {
      // Click event listener.
      accordion.addEventListener("click", () => {
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

  function searchAccordion(accordionElement) {
    const searchInput = accordionElement.querySelector("#accordionSearch");
    const searchButton = accordionElement.querySelector("#accordionSearchButton");

    searchButton.addEventListener("click", function () {
      const searchTerm = searchInput.value.toLowerCase();
      const accordionItems = accordionElement.querySelectorAll(".accordion-item");

      // Remove existing highlights
      const marks = accordionElement.querySelectorAll("mark");
      marks.forEach((mark) => {
        if (!mark || !mark.parentNode) return;
        const textContent = mark.textContent;
        const textNode = document.createTextNode(textContent);
        mark.parentNode.replaceChild(textNode, mark);
      });

      // Search through accordion items
      accordionItems.forEach((item) => {
        if (searchTerm === "") {
          item.style.display = "";
          return;
        }
        let hasMatch = false;

        const contentElements = item.querySelectorAll(".accordion-item__content p, .accordion-item__content h2, .accordion-item__content h3, .accordion-item__content h4, .accordion-item__content h4, .accordion-item__content h5, .accordion-item__content h6, .accordion-item__content li, .accordion-item__content blockquote, .accordion-item__content figcaption");

        contentElements.forEach((element) => {
          if (!element) return;

          const text = element.textContent.trim();
          if (text.toLowerCase().includes(searchTerm)) {
            hasMatch = true;
            const regex = new RegExp(`(${searchTerm})`, "gi");
            element.innerHTML = text.replace(
              regex,
              (match) => `<mark>${match}</mark>`,
            );
          }
        });

        // Show/hide based on match
        {
          if (hasMatch) {
            const titleElement = item.querySelector(".accordion-item__title");
            toggleAccordion(titleElement);
          }
        }
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".accordion").forEach((accordion) => {
      attachEventListeners(accordion);
      if (accordion.classList.contains("accordion--search")) {
        searchAccordion(accordion);
      }
    });
  });
})(Drupal);
