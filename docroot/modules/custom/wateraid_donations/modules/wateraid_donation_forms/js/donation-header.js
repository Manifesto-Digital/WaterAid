((Drupal, once) => {
  'use strict';

  Drupal.behaviors.wateraidDonationHeader = {
    attach(context) {
      const blockSiteBranding = once('donation-header-branding', '.webform-donations-page #block-sitebranding', context);
      const blockDonationStatusLink = once('donation-header-branding', '.webform-donations-page #block-donationstatus a', context);
      const donationHeaderFirstChild = once('donation-header-branding', '.webform-donations-page #block-donationheader li:first-child a', context);
      const donationHelpLayerClose = once('donation-header-branding', '.webform-donations-page .overlay-help-layer #close', context);
      const overlayHelpLayer = context.querySelector('.overlay-help-layer');

      blockSiteBranding[0] &&
        blockSiteBranding[0].addEventListener('click', (e) => {
          e.preventDefault();
          const donationDropDownBlock = context.querySelector(
            '#block-donationstatus'
          );
          donationDropDownBlock.style.display =
            donationDropDownBlock.style.display === 'block' ? 'none' : 'block';

          if (donationDropDownBlock.getAttribute('style') === null) {
            donationDropDownBlock.style.display = 'block';
          }

        blockSiteBranding[0].setAttribute(
          'aria-expanded',
          donationDropDownBlock.style.display === 'block' ? 'true' : 'false'
        );
      });


      blockDonationStatusLink[0] &&
        blockDonationStatusLink[0].addEventListener('click', (e) => {
          if (blockDonationStatusLink.getAttribute('href').length === 0) {
            e.preventDefault();
          }
          const donationDropDownBlock = context.querySelector(
            '#block-donationstatus'
          );
          donationDropDownBlock.style.display = 'none';
        });

      donationHeaderFirstChild[0] &&
        donationHeaderFirstChild[0].addEventListener('click', (e) => {
          e.preventDefault();
          overlayHelpLayer.style.display = 'block';
        });

      donationHelpLayerClose[0] &&
        donationHelpLayerClose[0].addEventListener('click', () => {
          overlayHelpLayer.style.display = 'none';
        });
    }
  };
})(Drupal, once);
