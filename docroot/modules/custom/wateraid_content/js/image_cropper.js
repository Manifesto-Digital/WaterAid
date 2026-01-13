(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.wateraidContentImageCropper = {
    attach: function (context, settings) {
      // Check if Cropper is available
      if (typeof Cropper === 'undefined') {
        console.error('Cropper.js library not loaded');
        return;
      }

      console.log('Image Cropper behavior attached',);

      // Find all layout paragraphs component forms
      if (!context.matches('form[data-drupal-selector="edit-layout-paragraphs-component-form-hero-image"]')) {
        return;
      }

      const form = context;

      console.log('Processing form for image cropper:', form);
      // // Check if this is a hero_image paragraph
      // const paragraphType = form.querySelector('input[name="type"]');
      // if (!paragraphType || paragraphType.value !== 'hero_image') {
      //   return;
      // }

      // // Check if field_image exists
      const imageField = form.querySelector('[data-drupal-selector="edit-field-image"] .fieldset__wrapper');
      // if (!imageField) {
      //   return;
      // }

      // Check if button already exists
      if (imageField.querySelector('.btn-show-crop')) {
        console.log('Crop button already exists, skipping.');
        return;
      }
      console.log('Adding crop button to image field.', imageField);

      // Create the "Show Crop" button
      const cropButton = document.createElement('button');
      cropButton.type = 'button';
      cropButton.className = 'btn-show-crop button';
      cropButton.textContent = 'Show Crop';

      // Insert button after the field widget
      const fieldWidget = imageField.querySelector('[data-drupal-selector="edit-group-image"]');
      console.log('Field widget for button insertion:', fieldWidget);
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

      function openCropModal(form, imageField) {
        // Find the image from the referenced media entity
        console.log('Opening crop modal for image field:', imageField);

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
              <h2 class="image-crop-modal__title">Crop Image</h2>
              <button type="button" class="image-crop-modal__close" aria-label="Close">&times;</button>
            </div>
            <div class="image-crop-modal__body">
              <div class="image-crop-modal__controls">
                <button type="button" class="btn-crop-zoom-in" title="Zoom In">Zoom In (+)</button>
                <button type="button" class="btn-crop-zoom-out" title="Zoom Out">Zoom Out (-)</button>
                <button type="button" class="btn-crop-rotate-left" title="Rotate Left">Rotate Left</button>
                <button type="button" class="btn-crop-rotate-right" title="Rotate Right">Rotate Right</button>
                <button type="button" class="btn-crop-flip-h" title="Flip Horizontal">Flip H</button>
                <button type="button" class="btn-crop-flip-v" title="Flip Vertical">Flip V</button>
                <button type="button" class="btn-crop-reset" title="Reset">Reset</button>
              </div>
              <div class="image-crop-container">
                <img id="crop-target-image" src="${imageSrc}" alt="Image to crop">
              </div>
              <div class="crop-info">
                <strong>Instructions:</strong> Drag the crop box to select the area you want to keep. Use the handles to resize.
              </div>
            </div>
            <div class="image-crop-modal__footer">
              <button type="button" class="btn-crop-cancel">Cancel</button>
              <button type="button" class="btn-crop-save button--primary">Save Crop Data</button>
            </div>
          </div>
        `;

        imageField.appendChild(modalOverlay);

        // Get the image element
        const cropTargetImage = imageField.querySelector('#crop-target-image');

        // Initialize Cropper.js
        const cropper = new Cropper(cropTargetImage, {
          aspectRatio: NaN, // Free aspect ratio
          viewMode: 1,
          dragMode: 'move',
          autoCropArea: 0.8,
          restore: false,
          guides: true,
          center: true,
          highlight: true,
          cropBoxMovable: true,
          cropBoxResizable: true,
          toggleDragModeOnDblclick: false,
          movable: true,
          resizable: true,
          centered: true
        });

        // Control buttons
        const zoomInBtn = modalOverlay.querySelector('.btn-crop-zoom-in');
        const zoomOutBtn = modalOverlay.querySelector('.btn-crop-zoom-out');
        const rotateLeftBtn = modalOverlay.querySelector('.btn-crop-rotate-left');
        const rotateRightBtn = modalOverlay.querySelector('.btn-crop-rotate-right');
        const flipHBtn = modalOverlay.querySelector('.btn-crop-flip-h');
        const flipVBtn = modalOverlay.querySelector('.btn-crop-flip-v');
        const resetBtn = modalOverlay.querySelector('.btn-crop-reset');
        const closeBtn = modalOverlay.querySelector('.image-crop-modal__close');
        const cancelBtn = modalOverlay.querySelector('.btn-crop-cancel');
        const saveBtn = modalOverlay.querySelector('.btn-crop-save');

        // Zoom controls
        zoomInBtn.addEventListener('click', function() {
          cropper.zoom(0.1);
        });

        zoomOutBtn.addEventListener('click', function() {
          cropper.zoom(-0.1);
        });

        // Rotation controls
        rotateLeftBtn.addEventListener('click', function() {
          cropper.rotate(-45);
        });

        rotateRightBtn.addEventListener('click', function() {
          cropper.rotate(45);
        });

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
          document.body.removeChild(modalOverlay);
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

          console.log('Crop data:', cropInfo);

          // Try to find or create a hidden field to store crop data
          let cropDataField = form.querySelector('input[name="field_image_crop_data"]');

          if (!cropDataField) {
            cropDataField = document.createElement('input');
            cropDataField.type = 'hidden';
            cropDataField.name = 'field_image_crop_data';
            form.appendChild(cropDataField);
          }

          cropDataField.value = JSON.stringify(cropInfo);

          // Optional: Generate cropped image canvas
          const canvas = cropper.getCroppedCanvas();
          if (canvas) {
            // You could potentially upload this to a field or show preview
            console.log('Cropped canvas generated:', canvas.width + 'x' + canvas.height);
          }

          alert('Crop data saved!\n\nCrop area: ' + cropInfo.width + 'x' + cropInfo.height + ' pixels\nPosition: (' + cropInfo.x + ', ' + cropInfo.y + ')');

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
