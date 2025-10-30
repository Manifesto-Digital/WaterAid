(function (Drupal, drupalSettings, $) {
  'use strict';

  console.log('AJAX OrangeDamLauncher loaded', drupalSettings.wa_orange_dam);

  Drupal.behaviors.waOrangeDamAjax = {
    attach: function (context, settings) {
      // Find DAM buttons in AJAX context
      const damButtons = context.querySelectorAll('#orange-dam-open');

      damButtons.forEach(function(damButton) {
        if (damButton.hasAttribute('data-dam-processed')) {
          return;
        }

        damButton.setAttribute('data-dam-processed', 'true');

        console.log('AJAX DAM Button found and processing', damButton);

        damButton.addEventListener('click', function (e) {
          e.preventDefault();
          console.log('AJAX DAM Button clicked');

          const types = settings.wa_orange_dam?.types || ['Images*'];

          OrangeDAMContentBrowser.open({
            onAssetSelected: (assets) => {
              console.log('Selected assets in AJAX context:', assets);

              // Find the system identifier field in the current context
              const identifierField = context.querySelector('#orange-dam-identifier');

              if (identifierField && assets.length > 0) {
                const systemId = assets[0].extraFields['CoreField.Identifier'];
                identifierField.value = systemId;

                // Trigger change event to update AJAX form
                const changeEvent = new Event('change', { bubbles: true });
                identifierField.dispatchEvent(changeEvent);

                // Also try triggering blur event for AJAX
                const blurEvent = new Event('blur', { bubbles: true });
                identifierField.dispatchEvent(blurEvent);

                console.log('Set system identifier:', systemId);
              }
            },
            onError: (errorMessage, error) => {
              const message = new Drupal.Message();
              message.add(errorMessage, {type: 'error'});
              console.log('DAM Error in AJAX context:', errorMessage, error);
            },
            containerId: '',
            extraFields: ['CoreField.Identifier', 'CoreField.Title', 'CustomField.Caption', 'customfield.Credit'],
            baseUrl: "https://dam.wi0.orangelogic.com/",
            availableDocTypes: types,
          });
        });
      });
    }
  };

  // Global function for media library integration
  window.damMediaCreated = function(mediaData) {
    console.log('Media created successfully:', mediaData);

    // Trigger Drupal event for media library
    $(document).trigger('damMediaCreated', [mediaData]);

    // If we're in a media library context, try to refresh the library
    if (typeof Drupal.MediaLibrary !== 'undefined') {
      // This would need to be integrated with the specific media library implementation
      console.log('Media library integration point');
    }
  };

  // Global function for showing messages
  window.showMessage = function(message, type) {
    type = type || 'status';
    const messageObj = new Drupal.Message();
    messageObj.add(message, {type: type});
  };

})(Drupal, drupalSettings, jQuery);
