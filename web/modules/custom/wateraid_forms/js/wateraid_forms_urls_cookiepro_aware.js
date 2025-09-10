/**
 * @file
 * Javascript behaviors for wateraid forms urls.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

    let urlParams;
    (window.onpopstate = function () {
      let match,
        pl     = /\+/g,  // Regex for replacing addition symbol with a space
        search = /([^&=]+)=?([^&]*)/g,
        decode = function (s) {
          return decodeURIComponent(s.replace(pl, " "));
        },
        query  = window.location.search.substring(1);

      urlParams = {};
      while (match = search.exec(query)) {
        urlParams[decode(match[1])] = decode(match[2]);
      }
    })();

    $.each(drupalSettings.wateraidForms.url_parameters, function (index, parameter_key) {
      let parameter_value = urlParams[parameter_key];

      let is_cookie_value_unpopulated = ($.cookie(parameter_key) === null || $.cookie(parameter_key) === "");
      let is_parameter_value_unpopulated = (parameter_value === null || parameter_value === "");

      if (!is_parameter_value_unpopulated) {
        $.cookie(parameter_key, parameter_value);
      }

      if (!is_cookie_value_unpopulated || !is_parameter_value_unpopulated) {
        $('form.webform-submission-form input[name=' + parameter_key + ']').val($.cookie(parameter_key));
      }
    });

})(jQuery, Drupal, drupalSettings);
