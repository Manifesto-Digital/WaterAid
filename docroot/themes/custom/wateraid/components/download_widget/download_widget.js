(function (Drupal) {
  Drupal.behaviors.downloadWidget = {
    attach(context) {
      function init(widget) {
        const select = widget.querySelector('#file-selector');

        const nameContainer = widget.querySelector(".download-widget__name");
        const formatContainer = widget.querySelector(".download-widget__format");
        const sizeContainer = widget.querySelector(".download-widget__size");
        const infoContainer = widget.querySelector('.download-widget__file-information');
        const buttonContainer = widget.querySelector('.button__wrapper');
        const button = widget.querySelector('.button');

        button.addEventListener('click', async function (event) {
          event.preventDefault();
          const url = button.getAttribute('href');
          if (!url || url === '#' || buttonContainer.classList.contains('button--disabled')) {
            return;
          }
          const filename = button.getAttribute('data-filename') || '';
          try {
            const response = await fetch(url);
            if (!response.ok) {
              throw new Error('Network response was not ok');
            }
            const blob = await response.blob();
            const blobUrl = URL.createObjectURL(blob);
            const tempLink = document.createElement('a');
            tempLink.href = blobUrl;
            tempLink.download = filename;
            document.body.appendChild(tempLink);
            tempLink.click();
            document.body.removeChild(tempLink);
            URL.revokeObjectURL(blobUrl);
          }
          catch (error) {
            window.location.href = url;
          }
        });

        select.onchange = function () {
          let selected = select.options[select.selectedIndex];
          // Show selected item information
          nameContainer.innerHTML = selected.getAttribute("data-name");
          formatContainer.innerHTML = selected.getAttribute("data-format");
          sizeContainer.innerHTML = selected.getAttribute("data-size");

          // Update button url
          const url = selected.getAttribute("data-url");
          const filename = selected.getAttribute("data-name") || '';

          if (url) {
            buttonContainer.classList.remove('button--disabled');
            button.setAttribute('href', url);
            button.setAttribute('data-filename', filename);
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
