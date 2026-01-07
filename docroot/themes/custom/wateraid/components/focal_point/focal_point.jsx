import React from 'react';
import ReactDOM from 'react-dom/client';
import ImageFocalPoint from '@lemoncode/react-image-focal-point';

// Expose a function globally for Drupal behaviors
window.initFocalPoint = (el, props) => {
  const root = ReactDOM.createRoot(el);
  root.render(<ImageFocalPoint {...props} />);
};

(function (Drupal) {
  'use strict';

  Drupal.behaviors.wateraidContentImageFocalPoint = {
    attach: function (context, settings) {
      console.log('Focal Point JS loaded');
    }
  };

})(Drupal);
