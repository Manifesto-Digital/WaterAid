(function (Drupal) {
  Drupal.behaviors.addressFocus = {
    attach: function (context) {
      // Plain JS version of jQuery is(':visible').
      function isVisible(elem) {
        return !!( elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length );
      }

      // Elements in reverse priority order of focus.
      var elements = [
        '.wa-subelement-reset',
        '.wa-subelement-address-picker',
        '.wa-subelement-address-line1'
      ];

      elements.forEach(function(elementSelector) {
        var element = context.querySelector(elementSelector);
        if (element && isVisible(element)) {
          element.focus();
        }
      });
    }
  };
})(Drupal);
