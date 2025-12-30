/**
 * @file
 * Javascript behaviors for donation webform upsell tooltip.
 *
 * Original author: guy.whale@conversion.com
 */

(function (w, Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.wateraidDonationsUpsell = {

    attach: function (context, settings) {

      let message = drupalSettings.wateraidDonationForms.upsell.message;
      let tag = drupalSettings.wateraidDonationForms.upsell.tag;

      const window = typeof unsafeWindow !== "undefined" ? unsafeWindow : w;

      const utils = {
        waitUntil: (condition, wait = 5000) => {
          return new Promise((resolve, reject) => {
            let stop;

            const timeout =
              wait &&
              setTimeout(() => {
                stop = true;
                reject();
              }, wait);

            const check = () => {
              if (stop) return;
              if (!condition()) return requestAnimationFrame(check);

              clearTimeout(timeout);
              resolve(condition());
            };

            requestAnimationFrame(check);
          });
        },
        watchDataLayer: (callback, wait = 5000) => {
          return new Promise((resolve, reject) => {
            let stop;

            const timeout =
              wait &&
              setTimeout(() => {
                stop = true;
                reject();
              }, wait);

            const check = () => {
              if (stop) return;

              if (typeof window.dataLayer === "undefined" || typeof window.dataLayer.push === "undefined")
                return requestAnimationFrame(check);

              clearTimeout(timeout);
              const push = window.dataLayer.push;

              window.dataLayer.push = function (data) {
                push.apply(this, arguments);
                resolve(callback(data));
              };
            };

            requestAnimationFrame(check);
          });
        },
      };

      init();

      function init() {
        utils
          .waitUntil(() => document.querySelector('body'), 0)
          .then((docBody) => {
            docBody.classList.add(`${tag}`);

            utils
              .waitUntil(() => document.querySelector('#edit-donation-amount-amount-recurring-amounts-buttons label[for="edit-donation-amount-amount-recurring-amounts-buttons-10"]'), 0)
              .then((donationamt) => {
                donationamt.insertAdjacentHTML('beforeend', `<div class="${tag}-tooltip"><p>${message}</p></div>`);


                // amount click
                document.querySelectorAll('#edit-donation-amount-amount-recurring-amounts-buttons input').forEach((inputEle) => {
                  inputEle.addEventListener('click', () => {
                    if (inputEle.value == '10') {
                      document.querySelector(`.${tag}-tooltip`).closest('label').classList.remove('show');
                      sessionStorage.setItem(tag, 'close');
                    } else {
                      clearTimeout(tooltipShow);
                      showTooltip();
                    }
                  });
                });

                // custom amount click
                document.addEventListener('click', (elem) => {
                  if (elem.target.classList.contains('cv-wtr-5-7__seven-label')) {
                    clearTimeout(tooltipShow);
                    showTooltip();
                  }
                  if (elem.target.closest('[data-drupal-selector="edit-section-goto-page-step-1"] [data-drupal-selector="edit-edit-step-1"]')) {
                    var checkTooltip = setInterval(() => {
                      if (document.querySelector('[for^="edit-donation-amount-amount-recurring-amounts-buttons-9"]') && !document.querySelector('.ui-state-active[for^="edit-donation-amount-amount-recurring-amounts-buttons-10"]') && !document.querySelector(`[for^="edit-donation-amount-amount-recurring-amounts-buttons-10"] .${tag}-tooltip`)) {
                        clearInterval(checkTooltip)
                        document.querySelector('[for^="edit-donation-amount-amount-recurring-amounts-buttons-10"]').insertAdjacentHTML('beforeend', `<div class="${tag}-tooltip"><p>${message}</p></div>`);
                        tooltipShow = setTimeout(() => {
                          showTooltip();
                        }, 5000);
                      }
                    }, 100);
                  }
                  if (elem.target.closest('[id^=edit-donation-amount-amount-recurring-amounts-buttons] input')) {
                    if (elem.target.value == '10') {
                      document.querySelector(`.${tag}-tooltip`).closest('label').classList.remove('show');
                      sessionStorage.setItem(tag, 'close');
                    } else {
                      clearTimeout(tooltipShow);
                      showTooltip();
                    }
                  }
                  if (elem.target.closest('.js-webform-buttons-other-input [id^="edit-donation-amount-amount-recurring-amounts-other"]')) {
                    document.querySelector(`.${tag}-tooltip`).closest('label').classList.remove('show');
                    sessionStorage.setItem(tag, 'close');
                  }
                });

                var tooltipShow;
                window.addEventListener('scroll', () => {
                  if (document.querySelector('#edit-donation-amount-amount-recurring-amounts-buttons label[for="edit-donation-amount-amount-recurring-amounts-buttons-10"]')?.closest('form').offsetTop - 500 < window.scrollY && sessionStorage.getItem(tag) !== 'close') {
                    tooltipShow = setTimeout(() => {
                      showTooltip();
                    }, 5000);
                  }
                })

                function showTooltip() {
                  if (sessionStorage.getItem(tag) !== 'close' && document.querySelector(`.${tag}-tooltip`)) {
                    document.querySelector(`.${tag}-tooltip`).closest('label').classList.add('show');
                    setTimeout(() => {
                      document.querySelector(`.${tag}-tooltip`).closest('label').classList.remove('show');
                      sessionStorage.setItem(tag, 'close');
                    }, 5000);
                  }
                }

              });
          })
          .catch((reason) => {
          });
      }
    }
  };
})(window, Drupal, drupalSettings);
