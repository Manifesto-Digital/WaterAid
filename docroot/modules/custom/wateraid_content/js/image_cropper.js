(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.wateraidContentImageCropper = {
    attach: function (context, settings) {
      // Check if Cropper is available
      if (typeof Cropper === 'undefined') {
        console.error('Cropper.js library not loaded');
        return;
      }

      // Find all layout paragraphs component forms
      if (!(context.matches('form[data-drupal-selector="edit-layout-paragraphs-component-form-hero-image"]')
        || context.matches('form[data-drupal-selector="edit-layout-paragraphs-component-form-hero-donate"]'))) {
        return;
      }

      const form = context;
      const imageField = form.querySelector('[data-drupal-selector="edit-field-image"] .fieldset__wrapper');

      // Check if button already exists
      if (imageField.querySelector('.btn-show-crop')) {
        return;
      }

      // Create the "Show Crop" button
      const cropButton = document.createElement('button');
      cropButton.type = 'button';
      cropButton.className = 'btn-show-crop button';
      cropButton.textContent = 'Show Crop';

      // Insert button after the field widget
      const fieldWidget = imageField.querySelector('[data-drupal-selector="edit-group-image"]');
      if (fieldWidget) {
        fieldWidget.appendChild(cropButton);
      } else {
        imageField.appendChild(cropButton);
      }

      // Handle button click
      cropButton.addEventListener('click', function(e) {
        e.preventDefault();
        openCropModal(form, imageField);
      });

      // If image is removed then clear the crop field.
      const removeButton = form.querySelector('input[name="field_image-0-media-library-remove-button"]');
      const cropField = form.querySelector('input[name="field_image_crop[0][value]"]');

      if (removeButton) {
        removeButton.addEventListener('mousedown', () => {
          cropField.value = '';
        })
      }

      function openCropModal(form, imageField) {
        // Find the image from the referenced media entity
        const mediaImage = imageField.querySelector('img');

        if (!mediaImage || !mediaImage.src) {
          alert('Please add an image first before cropping.');
          return;
        }

        const imageSrc = mediaImage.src;

        // Create modal overlay
        const modalOverlay = document.createElement('div');
        modalOverlay.className = 'image-crop-modal-overlay active';

        modalOverlay.innerHTML = `
          <div class="image-crop-modal">
            <div class="image-crop-modal__header">
              <h2 class="image-crop-modal__title ui-dialog-title">Crop Image</h2>
              <button type="button" class="image-crop-modal__close" aria-label="Close">&times;</button>
            </div>
            <div class="image-crop-modal__body">
              <div class="image-crop-modal__controls">
                <button type="button" class="btn-crop-flip-h button ui-button" title="Flip Horizontal">Flip H</button>
                <button type="button" class="btn-crop-flip-v button ui-button" title="Flip Vertical">Flip V</button>
                <button type="button" class="btn-crop-reset button ui-button" title="Reset">Reset</button>
              </div>
              <div class="image-crop-container">
                <img id="crop-target-image" src="${imageSrc}" alt="Image to crop">
              </div>
              <div class="crop-info">
                <strong>Instructions:</strong> Drag the crop box to select the area you want to keep. Use the handles to resize.
              </div>
            </div>
            <div class="image-crop-modal__footer">
              <button type="button" class="btn-crop-save button--primary button ui-button">Save Crop Data</button>
              <button type="button" class="btn-crop-cancel button ui-button">Cancel</button>
            </div>
          </div>
        `;

        imageField.appendChild(modalOverlay);

        // Get the image element
        const cropTargetImage = imageField.querySelector('#crop-target-image');

        const cropField = form.querySelector('input[name="field_image_crop[0][value]"]');

        let data;

        if (cropField.value) {
          data = JSON.parse(cropField.value);
        }

        // Initialize Cropper.js
        const cropper = new Cropper(cropTargetImage, {
          ready: function () {
            if (data) {
              cropper.setData(data);
            }
          },
          aspectRatio: 1440/480, // Free aspect ratio
          viewMode: 1,
          dragMode: 'move',
          autoCropArea: 1,
          restore: false,
          guides: true,
          center: true,
          highlight: true,
          cropBoxMovable: true,
          cropBoxResizable: true,
          toggleDragModeOnDblclick: false,
          movable: true,
          resizable: false,
          centered: true
        });

        // Control buttons
        const flipHBtn = modalOverlay.querySelector('.btn-crop-flip-h');
        const flipVBtn = modalOverlay.querySelector('.btn-crop-flip-v');
        const resetBtn = modalOverlay.querySelector('.btn-crop-reset');
        const closeBtn = modalOverlay.querySelector('.image-crop-modal__close');
        const cancelBtn = modalOverlay.querySelector('.btn-crop-cancel');
        const saveBtn = modalOverlay.querySelector('.btn-crop-save');

        // Flip controls
        let scaleX = 1;
        let scaleY = 1;

        flipHBtn.addEventListener('click', function() {
          scaleX = -scaleX;
          cropper.scaleX(scaleX);
        });

        flipVBtn.addEventListener('click', function() {
          scaleY = -scaleY;
          cropper.scaleY(scaleY);
        });

        // Reset
        resetBtn.addEventListener('click', function() {
          cropper.reset();
          scaleX = 1;
          scaleY = 1;
        });

        // Close modal function
        function closeModal() {
          cropper.destroy();
          modalOverlay.remove();
        }

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Save crop data
        saveBtn.addEventListener('click', function() {
          const cropData = cropper.getData(true);
          const imageData = cropper.getImageData();

          // Prepare crop information
          const cropInfo = {
            x: Math.round(cropData.x),
            y: Math.round(cropData.y),
            width: Math.round(cropData.width),
            height: Math.round(cropData.height),
            rotate: Math.round(cropData.rotate),
            scaleX: cropData.scaleX,
            scaleY: cropData.scaleY,
            imageWidth: Math.round(imageData.naturalWidth),
            imageHeight: Math.round(imageData.naturalHeight)
          };

          const cropField = form.querySelector('input[name="field_image_crop[0][value]"]');
          cropField.value = JSON.stringify(cropInfo);

          closeModal();
        });

        // Close on overlay click
        modalOverlay.addEventListener('click', function(e) {
          if (e.target === modalOverlay) {
            closeModal();
          }
        });
      }
    }
  };

})(Drupal, once);
