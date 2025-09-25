(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.wateraidDonationsConfirmation = {
    attach: function (context, settings) {
      let $summaryButton = $(".webform-confirmation__donation-confirmation .header button");
      let $summaryContent = $(".webform-confirmation__donation-confirmation .container .content");
      let showMessage = Drupal.t('Show Confirmation Summary');
      let hideMessage = Drupal.t('Hide Confirmation Summary');

      // Remove classes that prevent the header from appearing as a donation header
      $("body").addClass("donations-confirmation-page").removeClass('minimal-header light--header');

      // Add slider effect to payment confirmation page donation details.
      $summaryButton.click(function () {
        $summaryContent.slideToggle(500, function () {
          // Change button text
          if ($summaryButton.text() === showMessage) {
            $summaryButton.text(hideMessage);
          } else {
            $summaryButton.text(showMessage);
          }
        });
      });
      $summaryContent.toggle().hide();

      // If the shop code widget is present, add copy behaviour
      const copyButton = context.querySelector('.shop-code-widget--button');
      const offerCode = context.querySelector('.shop-code-widget--code').textContent;

      if (copyButton && offerCode) {
        copyButton.addEventListener("click", function () {
          navigator.clipboard.writeText(offerCode)
            .then(() => {
              copyButton.innerHTML = "Copied!";
            })
            .catch((err) => {
              console.error("Failed to copy text: ", err);
            }
          );
        });
      }
      
      // If a donation reminder is set, remove it
      sessionStorage.removeItem('last_one-off_donation');
    }
  };

})(jQuery, Drupal, drupalSettings);
