(function (Drupal, DrupalSettings) {

  document.addEventListener("DOMContentLoaded", function () {
    const damButton = document.getElementById('orange-dam-open');

    damButton.addEventListener("click", function (e) {
      e.preventDefault();

      OrangeDAMContentBrowser.open({
        onAssetSelected: (assets) => {
          document.getElementById('orange-dam-identifier').value = assets[0].extraFields['CoreField.Identifier'];
        },
        onError: (errorMessage, error) => {
          const message = new Drupal.Message();
          message.add(errorMessage, {type: 'error'});
        },
        containerId: '',
        extraFields: ['CoreField.Identifier'],
        baseUrl: "https://dam.wi0.orangelogic.com/",
        availableDocTypes: drupalSettings.wa_orange_dam.types,
      });
    });
  });
})(Drupal);
