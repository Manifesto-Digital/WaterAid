(function (Drupal, drupalSettings, $) {
  'use strict';

  Drupal.behaviors.waOrangeDamAjax = {
    attach: function (context, settings) {
      // Find DAM buttons in AJAX context
      const damButtons = context.querySelectorAll('#orange-dam-open');

      damButtons.forEach(function(damButton) {
        if (damButton.hasAttribute('data-dam-processed')) {
          return;
        }

        damButton.addEventListener('click', function (e) {
          e.preventDefault();
          const types = settings.wa_orange_dam?.types || ['Images*'];

          OrangeDAMContentBrowser.open({
            onAssetSelected: (assets) => {

              // Find the system identifier field in the current context.
              const identifierField = context.querySelector('#orange-dam-identifier');

              if (identifierField && assets.length > 0) {

                // Send AJAX request to server to create media entity.
                const ajax = Drupal.ajax({
                  url: '/admin/orange-dam/ajax/create-media',
                  submit: {
                    media_type: types,
                    asset_id: assets[0].extraFields['CoreField.Identifier'],
                  },
                  progress: {
                    type: 'throbber',
                    message: 'Creating media...'
                  }
                });

                ajax.execute();

              }
            },
            onError: (errorMessage, error) => {
              const message = new Drupal.Message();
              message.add(errorMessage, {type: 'error'});
              console.log('DAM Error in:', errorMessage, error);
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

})(Drupal, drupalSettings, jQuery);
