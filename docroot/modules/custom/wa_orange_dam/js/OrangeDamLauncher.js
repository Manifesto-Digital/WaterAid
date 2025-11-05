(function (Drupal, DrupalSettings) {

  document.addEventListener("DOMContentLoaded", function () {
    const damButton = document.getElementById('orange-dam-open');

    damButton.addEventListener("click", function (e) {
      e.preventDefault();

      OrangeDAMContentBrowser.open({
        onAssetSelected: (assets) => {
          document.getElementById('orange-dam-identifier').value = assets[0].extraFields['CoreField.Identifier'];

          const name = document.querySelector('[data-drupal-selector="edit-name-0-value"]');
          const caption = document.querySelector('[data-drupal-selector="edit-field-caption-0-value"]');
          const credit = document.querySelector('[data-drupal-selector="edit-field-credit-0-value"]');

          if (name) {
            name.value = assets[0].extraFields['CoreField.Title'] || 'DAM asset ' + assets[0].extraFields['CoreField.Identifier'];
          }
          if (caption) {
            caption.value = assets[0].extraFields['CustomField.Caption'];
          }
          if (credit) {
            credit.value = assets[0].extraFields['customfield.Credit'];
          }
        },
        onError: (errorMessage, error) => {
          console.log('DAM Error:', errorMessage, error);
          const message = new Drupal.Message();
          message.add(errorMessage, {type: 'error'});
        },
        containerId: '',
        extraFields: ['CoreField.Identifier', 'CoreField.Title', 'CustomField.Caption', 'customfield.Credit'],
        baseUrl: "https://dam.wi0.orangelogic.com/",
        availableDocTypes: drupalSettings.wa_orange_dam.types,
      });
    });
  });
})(Drupal);
