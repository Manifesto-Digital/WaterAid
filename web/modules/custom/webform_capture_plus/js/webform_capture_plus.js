/**
 * @file
 * Javascript behaviors for webform address postcode lookup.
 */

(function ($, Drupal, once) {

  'use strict';

  let pca_loosefocus = true;
  let pca_address_selected = false;

  // Update default validation on-blur / focusout of PCA field to add a delay -
  // this prevents validation from failing while user navigates away from search
  // field to select an address from PCA list.
  let pcaFocusOutTimeout;
  let originalFocusOut = $.validator.defaults.onfocusout;
  const focusout = drupalSettings.cvJqueryValidateOptions.onfocusout = function (element, event) {
    if ($(element).hasClass('search-address-field')) {
      clearTimeout(pcaFocusOutTimeout);
      const thisInput = this;
      pcaFocusOutTimeout = setTimeout(function () {
        $(element).valid();
        return thisInput.element(element);
      }, 400);
    }
    else {
      return originalFocusOut.call(this, element, event);
    }
  };

  $.extend(originalFocusOut, focusout);

  /**
   * Add behaviours for donation webform elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformCapturePlus = {
    attach: function (context) {
      const manualElement = $(".wa-subelement-wrapper-capture-plus[data-pcamanual='pcamanual']", context);
      if (manualElement.length > 0) {
        manualElement.show();
        $(".wa-subelement-wrapper-capture-plus [data-paf]").val('manual');
        return;
      }

      function Capture_Interactive_Retrieve_v1_00(Key, Id, key) {

        $.getJSON('https://services.postcodeanywhere.co.uk/Capture/Interactive/Retrieve/v1.00/json3.ws?callback=?',
          {
            Key: Key,
            Id: Id
          },
          function (data) {
            // Test for an error.
            if (data.Items.length === 1 && typeof(data.Items[0].Error) !== 'undefined') {
              // Show the error message.
              alert(data.Items[0].Description);
            }
            else {
              // Check if there were any items found.
              if (data.Items.length === 0) {
                alert(Drupal.t('Sorry, there were no results'));
              }
              else {
                let address1 = '';
                let joined_items;
                // If there is a company name then add it to the first address line.
                if (data.Items[0].Company !== '') {
                  address1 = data.Items[0].Company + ', ';
                }
                address1 += data.Items[0].Line1;

                let missing_field = [];

                // Check to see if we have a postcode field. If so then add the data to it, otherwise
                // store it for the next field.
                let postalCode = $("input[name='" + key + "[postal_code]']");
                if (postalCode.length) {
                  postalCode.val(data.Items[0].PostalCode).valid();
                  missing_field = [];
                }
                else {
                  // No post code field so store it for the next one.
                  missing_field.unshift(data.Items[0].PostalCode);
                }

                // Do we have a state province field?
                if ($("input[name='" + key + "[state_province]']", context).length) {
                  if (data.Items[0].ProvinceName.length) {
                    // Add the province field to the missing_fields.
                    missing_field = $.merge([data.Items[0].ProvinceName], missing_field);
                    // Remove any blank items and then join them together.
                    joined_items = $.grep(missing_field, function (n) {
                      return (n);
                    }).join(", ");
                    $("input[name='" + key + "[state_province]']", context).val(joined_items).valid();
                    missing_field = [];
                  }
                }
                else {
                  // No province field so add it to the missing fields.
                  missing_field.unshift(data.Items[0].ProvinceName);
                }

                // Check the city field exists.
                if ($("input[name='" + key + "[city]']", context).length) {
                  // Add the city field to the missing fields.
                  missing_field = $.merge([data.Items[0].City], missing_field);
                  // Remove any blank items and then join them together.
                  joined_items = $.grep(missing_field, function (n) {
                    return n;
                  }).join(", ");
                  $("input[name='" + key + "[city]']", context).val(joined_items).valid();
                  missing_field = [];
                }
                else {
                  // No city field so add it to the missing fields.
                  missing_field.unshift(data.Items[0].City);
                }

                // Check the address 2 field exists.
                if ($("input[name='" + key + "[address_2]']", context).length) {
                  // Add all other address fields to the missing field.
                  missing_field = $.merge([
                    data.Items[0].Line2,
                    data.Items[0].Line3,
                    data.Items[0].Line4,
                    data.Items[0].Line5
                  ], missing_field);
                  // Remove any blank items and then join them together.
                  joined_items = $.grep(missing_field, function (n) {
                    return (n);
                  }).join(", ");
                  $("input[name='" + key + "[address_2]']", context).val(joined_items);
                  missing_field = [];
                }
                else {
                  // Address field is not present so add the missing address fields to missing field.
                  missing_field.unshift(data.Items[0].Line5);
                  missing_field.unshift(data.Items[0].Line4);
                  missing_field.unshift(data.Items[0].Line3);
                  missing_field.unshift(data.Items[0].Line2);
                }

                missing_field = $.merge([address1], missing_field);
                // Remove any blank items and then join them together.
                joined_items = $.grep(missing_field, function (n) {
                  return (n);
                }).join(', ');
                $("input[name='" + key + "[address]']", context).val(joined_items);

                // Lookup country from results in select list and set selected option.
                if ($("select[name='" + key + "[country]']", context).length) {
                  let country_key = data.Items[0].CountryName;
                  if ($("select[name='" + key + "[country]'] option[value='" + country_key + "']", context).length) {
                    $("select[name='" + key + "[country]'] option[value='" + country_key + "']", context).prop('selected', true);
                    $("select[name='" + key + "[country]'] option[value='" + country_key + "']", context).prop('disabled', false);
                  }
                }
                $('.pcaautocomplete.pcatext', context).hide();

                $('.lookup-results', context).html(nl2br(data.Items[0].Label));
                $(".wa-subelement-wrapper-capture-plus [data-paf]").val('lookup');
              }
            }
          }
        );
      }

      function Capture_Interactive_Find_v1_00(Key, Text, Container, Origin, Countries, Limit, Language, key) {

        $.getJSON('https://services.postcodeanywhere.co.uk/Capture/Interactive/Find/v1.00/json3.ws?callback=?',
          {
            Key: Key,
            Text: Text,
            Container: Container,
            Origin: Origin,
            Countries: Countries,
            Limit: Limit,
            Language: Language
          },
          function (data) {
            // Check if the string isn't just whitespaces.
            if (Text && !Text.trim()) {
              return '';
            }

            // Test for an error.
            if (data.Items.length === 1 && typeof(data.Items[0].Error) !== 'undefined') {
              // Show the error message.
              alert(data.Items[0].Description);
            }
            else {
              // Check if there were any items found.
              if (data.Items.length === 0) {
                return '';
              }
              else {
                // FYI: The output is a JS object (e.g. data.Items[0].Id), the keys being:
                // Id
                // Type
                // Text
                // Highlight
                // Description
                let results = '';
                let length = data.Items.length;
                let itemCount = 0;
                let firstClass = '';
                let lastClass = '';

                // Add each result as a list item.
                $.each(data.Items, function (key, value) {
                  firstClass = '';
                  lastClass = '';
                  itemCount++;
                  if (itemCount === 1) {
                    firstClass = ' pcafirstitem';
                  }
                  if (itemCount === length) {
                    lastClass = ' pcalastitem';
                  }
                  results += '<li role="option" aria-selected="false" aria-setsize="' + length + '" aria-posinset="' + itemCount + '" tabindex="-1" class="pcaitem' + firstClass + lastClass + '" id="' + value.Id + '" title="' + value.Text + '" type="' + value.Type + '">' + value.Text + '<span class="pcadescription">' + value.Description + '</span></li>';
                });

                let listItemClick = function (selectedItem) {
                  if ($(selectedItem, context).attr('Type') === 'Address') {
                    Capture_Interactive_Retrieve_v1_00(pcaApiKey, $(selectedItem, context).attr('id'), key);
                    $('select[name^=' + key + '\\[] option:not(:selected)', context).prop('disabled', 'true');
                    // Show the selected address box.
                    $('.lookup-results', context).removeClass('hidden');
                    $('.search-address-field', context).valid();
                    pca_address_selected = true;
                  }
                  else {
                    Capture_Interactive_Find_v1_00(pcaApiKey, $(selectedItem, context).attr('title'), $(selectedItem, context).attr('id'), '', '', 10, '', key);
                  }
                };

                let a11yClick = function (event) {
                  if (event.type === 'click') {
                    listItemClick(this);
                  }
                  else if (event.type === 'keypress') {
                    let code = event.charCode || event.keyCode;
                    if ((code === 32)|| (code === 13)) {
                      listItemClick(this);
                    }
                  }
                  else {
                    return false;
                  }
                };

                $('.pca.pcalist', context).html(results);
                $('.pcaitem', context)
                  .hover(
                    function () {
                      $(this).addClass('pcaselected');
                    },
                    function () {
                      $(this).removeClass('pcaselected');
                    }
                  )
                  .click(a11yClick)
                  .keypress(a11yClick);
              }
            }
          }
        );
      }

      function nl2br(str, is_xhtml) {
        let breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>';
        return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
      }

      function createPcaHtml(event) {
        let context = event.data.context;
        let manualButton = $('.capture-manual', context).detach();
        $('fieldset.js-webform-address', context).append(
          '<div class="pca"><div class="pcaautocomplete pcatext"><ul class="pca pcalist" tabindex="-1" role="listbox"></ul></div></div>'
        )
          .append(manualButton);
        manualButton.show();
      }

      const pcaApiKey = drupalSettings.webformCapturePlus['apiKey'];

      // Lose focus on autocomplete when mousedown detected.
      $('.pcaautocomplete.pcatext', context).on('mousedown', function () {
        pca_loosefocus = false;
      });

      // Try and manage multiple address fields in the form.
      $.each(drupalSettings.webformCapturePlus['keys'], function (key, value) {

        // Be careful with the selectors; either to start with a string and
        // opening bracket or the full known string to not conflict with other
        // selectors.
        // Disable autofill on all address fields & make all address fields read-only.
        $('[name=' + key + ']:not(.search-address-field), [name^=' + key + '\\[]:not(.search-address-field)', context)
          .attr('autocomplete', 'false')
          .prop('readonly', 'true')
          .addClass('disabled');
        $('[name^=' + key + '\\[]', context)
          .find('option:not(:selected)').prop('disabled', 'true');

        // Hide the lookup results area until there are results to show.
        $('.lookup-results', context).addClass('hidden');

        let lookupFieldId = 'edit-contact-address-lookup--wrapper';

        // Generate a random suffix for the pcalookup field (so autocomplete probably won't have previous values)
        let firstPart = (Math.random() * 46656) | 0;
        let secondPart = (Math.random() * 46656) | 0;
        firstPart = ('000' + firstPart.toString(36)).slice(-3);
        secondPart = ('000' + secondPart.toString(36)).slice(-3);
        let inputName = key + '[pcalookup_' + firstPart + secondPart + ']';
        if ($('#' + lookupFieldId).length === 0) {
          // Inject a new pcalookup field into the form.
          let newInput = '';
          if (drupalSettings.webformCapturePlus['required']) {
            newInput += '<fieldset class="required wa-element-type-webform-address fieldgroup form-composite js-webform-address webform-address js-form-item form-item js-form-wrapper form-wrapper" required="required" aria-required="true" id="' + lookupFieldId + '">'
          }
          else {
            newInput += '<fieldset class="wa-element-type-webform-address fieldgroup form-composite js-webform-address webform-address js-form-item form-item js-form-wrapper form-wrapper"  aria-required="false" id="' + lookupFieldId + '">'
          }

          newInput += '<div class="js-form-item form-item js-form-type-textfield">';
          if (drupalSettings.webformCapturePlus['required']) {
            newInput += '<label for="' + inputName + '" class="js-form-required form-required">' + drupalSettings.webformCapturePlus['lookupLabel'] + '</label>';
          }
          else {
            newInput += '<label for="' + inputName + '">' + drupalSettings.webformCapturePlus['lookupLabel'] + '</label>';
          }
          newInput += '<input name="' + inputName + '" id="' + inputName + '" type="text" required="required" placeholder="' + drupalSettings.webformCapturePlus['placeholder'] + '" aria-required="true" autocomplete="false" aria-owns="pcalist" class="search-address-field" aria-activedescendant="" aria-controls="pcalist" aria-autocomplete="list" />';
          newInput += '</div>';
          newInput += '</fieldset>';

          let lookupResults = '<p class="lookup-results"></p>';

          // Add a new fieldset above the fieldset containing the address field.
          $("input[name='" + key + "[address]']", context).parents('.pca-wrapper').prepend(lookupResults).prepend(newInput);

          // Add validation rule to address search field to check an address
          // has been selected.
          if (drupalSettings.webformCapturePlus['required']) {
            $.validator.addMethod('checkValidPCA', function (value, element) {
              const $wrapper = $(element).closest('.pca-wrapper');
              const $results = $wrapper.find('.lookup-results');
              // Check whether pca results are populated.
              return !$results.hasClass('hidden');
            }, Drupal.t('Please search for your address or enter an address manually below.'));

            $('.search-address-field', context).each(function (index, element) {
              $(element).rules('add', {
                checkValidPCA: true
              });
            })
          }
        }

        // Create the base HTML for the autocomplete.
        // @see: docroot/themes/custom/wateraid_base_theme/js/scripts.js.
        if (document === context) {
          $('form.webform-submission-form', context).on('webform-loaded', {context: context}, createPcaHtml);
        }
        else {
          createPcaHtml({data: {context: context}});
        }

        let hasInput = false;
        let inputValues = [];
        // Determine mandatory check.
        $.each(['address', 'address_2', 'postal_code', 'city', 'country'], function (index, input) {
          if ($("input[name='" + key + "[" + input + "]']", context).length && $("input[name='" + key + "[" + input + "]']", context).val()) {
            // Gather values from input fields.
            inputValues.push($("input[name='" + key + "[" + input + "]']", context).val());
            hasInput = pca_address_selected = true;
          }
          else if ($("select[name='" + key + "[" + input + "]']", context).length && $("select[name='" + key + "[" + input + "]']", context).val()) {
            // Gather values from input fields.
            inputValues.push($("select[name='" + key + "[" + input + "]']", context).val());
            hasInput = pca_address_selected = true;
          }
        });

        if (hasInput === true) {
          // Do remove the JS required class.
          $('#' + lookupFieldId, context).find('label').removeClass('js-form-required');
          // Do remove the input required attribute.
          $('#' + lookupFieldId, context).find('input').removeAttr('required');
          // Fetch details from form state to lookup markup.
          $('.lookup-results', context).html(inputValues.join('<br>'));
        }

        // Listen for input in the injected address lookup field.
        $(once('pca_checker', "input[name='" + inputName + "']", context))
          .on('input', function () {

            // Address needs to be checked again.
            pca_address_selected = false;

            // Clear the address fields.
            $('[name=' + key + '], [name^=' + key + '\\[]', context).not("input[name='" + inputName + "']").val(null);
            $('.lookup-results', context).addClass('hidden');
            $('.lookup-results', context).html(null);

            if ($(this).val() === '') {
              $('.pcaautocomplete.pcatext', context).hide();
            }
            else {
              let position = $(this).position();
              let height = $(this).outerHeight();

              $('.pcaautocomplete.pcatext', context).css({left: position.left, top: position.top + height}).show();
              Capture_Interactive_Find_v1_00(pcaApiKey, $(this).val(), '', '', '', 10, '', key);
            }
          })
          .keypress(function (event) {
            // Disable enter key in lookup field.
            if (event.keyCode === 13) {
              event.preventDefault();
            }
          })
          .focusout(function () {
            // Hide address list if the user clicks the page body while browsing addresses.
            if (pca_loosefocus) {
              setTimeout(function () {
                if (!document.activeElement.classList.contains('pcaitem')) {
                  $('.pcaautocomplete.pcatext', context).hide();
                }
              }, 50);
            }
            else {
              pca_loosefocus = true;
            }
          });

        $('input', context)
            .focus(function () {
              // Hide address list upon a different input being focused.
              if (!document.activeElement.classList.contains('pcaitem')) {
                $('.pcaautocomplete.pcatext', context).hide();
              }
            });

      });

      /**
       * Autocomplete accessibility.
       * Keyboard keys covered: Down Arrow, Up Arrow, Enter, Esc.
       *
       * Down Arrow:
       * - If the listbox is displayed: Moves focus to the first suggested value.
       * - If item already selected, moves focus to the next one.
       *
       * Up Arrow:
       * - If the listbox is displayed, moves focus to the last suggested value.
       * - If item already selected, moves focus to the previous one.
       *
       * Enter:
       * - Select an item.
       *
       * Escape:
       * - Clears the textbox.
       * - If the listbox is displayed, closes it.
       */
      $('form.webform-submission-form', context).on('webform-loaded', function () {
        let autocomplete = $('.pcaautocomplete', context);
        let addressInput = $('.search-address-field', context);
        let list = $('.pcalist', context);
        let arrowDirection = {
          up: 'up',
          down: 'down'
        }
        let keys = {
          left: 37,
          up: 38,
          right: 39,
          down: 40,
          enter: 13,
          esc: 27
        };

        function setItemFocus(el) {
          let listEl = list[0];
          let itemEl = el[0];
          el.attr('aria-selected', 'true').addClass('pcaselected');
          addressInput.attr('aria-activedescendant', itemEl.id);

          // Stay in list view while navigating through items.
          if (listEl.scrollHeight > listEl.clientHeight) {
            let scrollBottom = listEl.clientHeight + listEl.scrollTop;
            let elementBottom = itemEl.offsetTop + itemEl.offsetHeight;
            if (elementBottom > scrollBottom) {
              listEl.scrollTop = elementBottom - listEl.clientHeight;
            } else if (itemEl.offsetTop < listEl.scrollTop) {
              listEl.scrollTop = itemEl.offsetTop;
            }
          }
        }

        function removeItemFocus(el) {
          el.attr('aria-selected', 'false').removeClass('pcaselected');
          addressInput.attr('aria-activedescendant', '');
        }

        function moveFocus(direction, selectedItem) {
          let items = $(autocomplete.find('.pcaitem'));

          // Focus first or last item in the list if item not selected yet.
          if (!selectedItem.length) {
            if (direction === arrowDirection.up) {
              setItemFocus($(items[items.length - 1]));
            } else if (direction === arrowDirection.down) {
              setItemFocus($(items[0]));
            }
            return;
          }

          // Focus next or prev item. If focus is on the last item
          // then focus the first one and vice versa.
          removeItemFocus(selectedItem);
          if (direction === arrowDirection.up) {
            if (!selectedItem.prev().length) {
              setItemFocus($(items[items.length - 1]));
              return;
            }
            setItemFocus(selectedItem.prev());
          } else if (direction === arrowDirection.down) {
            if (!selectedItem.next().length) {
              setItemFocus($(items[0]));
              return;
            }
            setItemFocus(selectedItem.next());
          }
        }

        $('#edit-contact-address-lookup--wrapper', context).keydown(function (e) {
          let selected = autocomplete.find('.pcaitem[aria-selected="true"]');
          if (autocomplete.find('.pcaitem').length) {
            switch (e.which) {
              case keys.up:
                e.preventDefault();
                moveFocus(arrowDirection.up, selected);
                break;

              case keys.down:
                e.preventDefault();
                moveFocus(arrowDirection.down, selected);
                break;

              case keys.esc:
                e.preventDefault();
                addressInput.val('');
                autocomplete.hide();
                list.html('');
                break;

              case keys.enter:
                e.preventDefault();
                if (autocomplete.is(':visible') && selected.length) {
                  selected.click();
                }
                break;

              case keys.left:
              case keys.right:
                removeItemFocus(selected);
                break;

            }
          }
        });

        addressInput.on('focus', function () {
          autocomplete.show();
        });
      });
    }
  };

})(jQuery, Drupal, once);
