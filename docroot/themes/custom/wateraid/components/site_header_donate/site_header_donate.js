(function(Drupal) {

  Drupal.behaviors.siteHeaderDonate = {
    attach(context) {
      // Open and close a check if user clicks on WaterAid logo.
      const openCheckButton = context.querySelector('.site-header__logo-link');
      const closeCheckButton = context.querySelector('.site-header__check-close');
      const checkContainer = context.querySelector('.site-header__check');

      openCheckButton.addEventListener('click', () => {
        checkContainer.classList.toggle('open');
      });
      closeCheckButton.addEventListener('click', () => {
        checkContainer.classList.remove('open');
      });
    }
  };

})(Drupal);
