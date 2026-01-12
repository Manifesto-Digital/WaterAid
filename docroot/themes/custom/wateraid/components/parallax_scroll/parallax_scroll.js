(function (Drupal) {
  Drupal.behaviors.parallaxScroll = {
    attach: function (context) {
      const sections = context.querySelectorAll(".section");
      if (sections[0]) {
        sections[0].classList.add("active");
      }
      // Set section initial height
      const setSectionHeight = () => {
        sections.forEach((section) => {
          section.style.height = `${section.offsetHeight}px`;
        });
      };

      setSectionHeight();

      // 2. Efficiently track active section using Intersection Observer
      const observerOptions = {
        root: null, // use the viewport
        threshold: 0.5, // trigger when 50% of the section is visible
      };

      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            // Remove active state from current active element
            const currentActive = context.querySelector(".active");

            if (currentActive) {
              currentActive.classList.remove("active");
            }

            // Add active state to the visible section
            entry.target.classList.add("active");
          }
        });
      }, observerOptions);

      sections.forEach((section) => observer.observe(section));

      // Set scroll height on desktop
      const setScrollHeight = () => {
        const twoColumns = document.querySelectorAll(
          ".parallax-scroll--two-column",
        );
        let width = document.body.clientWidth;

        if (twoColumns && width > 1024) {
          twoColumns.forEach((component) => {
            const sectionImages = component.querySelectorAll(".section__image");
            console.log(sectionImages);
            console.log(component.offsetHeight);
            sectionImages.forEach((image) => {
              image.style.height = `${component.offsetHeight}px`;
            });
          });
        }
      };

      setScrollHeight();

      // Optional: Update heights if the window is resized
      window.addEventListener("resize", setSectionHeight);
      window.addEventListener("resize", setScrollHeight);
    },
  };
})(Drupal);
