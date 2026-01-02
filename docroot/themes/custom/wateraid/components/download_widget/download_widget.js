(function (Drupal) {
  Drupal.behaviors.downloadWidget = {
    attach(context) {
      function init(widget) {
        const select = widget.querySelector('#file-selector');

        const nameContainer = widget.querySelector(".download-widget__name");
        const formatContainer = widget.querySelector(".download-widget__format");
        const sizeContainer = widget.querySelector(".download-widget__size");
        select.onchange = function () {
          let selected = select.options[select.selectedIndex];
          // Show selected item information
          nameContainer.innerHTML = selected.getAttribute("data-name");
          formatContainer.innerHTML = selected.getAttribute("data-format");
          sizeContainer.innerHTML = selected.getAttribute("data-size");

          // Update button url
          const url = selected.getAttribute("data-url");

          const infoContainer = widget.querySelector('.download-widget__file-information');
          const buttonContainer = widget.querySelector('.button__wrapper');
          const button = widget.querySelector('.button');
          if (url) {
            buttonContainer.classList.remove('button--disabled');
            button.setAttribute('href', url);
            button.setAttribute('download', selected.getAttribute("data-name"));
            button.setAttribute('target', '_blank');
            infoContainer.style.display = 'flex';
          }
          else {
            buttonContainer.classList.add('button--disabled');
            infoContainer.style.display = 'none';
          }
        };
        select.onchange();
        }

      context.querySelectorAll(".download-widget").forEach((widget) => {
        init(widget);
      });
    },
  };
})(Drupal);
