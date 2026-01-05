(function (Drupal) {
  Drupal.behaviors.scrollmation = {
    attach: function (context) {
      const sections = document.querySelectorAll(".section");
      sections[0].classList.add("active");

      // 1. Set initial height
      const setHeight = () => {
        sections.forEach((section) => {
          section.style.height = `${section.offsetHeight}px`;
        });
      };

      setHeight();

      // 2. Efficiently track active section using Intersection Observer
      const observerOptions = {
        root: null, // use the viewport
        threshold: 0.5, // trigger when 50% of the section is visible
      };

      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            // Remove active state from current active element
            const currentActive = document.querySelector(".active");

            if (currentActive) {
              currentActive.classList.remove("active");
            }

            // Add active state to the visible section
            entry.target.classList.add("active");
          }
        });
      }, observerOptions);

      sections.forEach((section) => observer.observe(section));

      // Optional: Update heights if the window is resized
      window.addEventListener("resize", setHeight);

      context.querySelectorAll(".scrollmation").forEach((scrollmation) => {
        createScrollMation(scrollmation);
        context.addEventListener("scroll", () => {
          createScrollMation(scrollmation);
        });
      });
    },
  };
})(Drupal);
