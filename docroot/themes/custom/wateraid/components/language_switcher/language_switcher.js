(function(Drupal, once) {

  Drupal.behaviors.languageSwitcher = {
    attach(context) {

      once('language-switcher', '.language-switcher', context).forEach((switcher) => {
        const select = switcher.querySelector('.js-language-switcher-select');
        select.addEventListener('change', (e) => {
          const el = e.target;
          window.location.href = el.selectedOptions[0].dataset.jumpUrl;
        });
      });
    },
  };

})(Drupal, once);
