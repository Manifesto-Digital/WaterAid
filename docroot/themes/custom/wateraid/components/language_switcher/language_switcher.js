(function(Drupal, once) {

  Drupal.behaviors.languageSwitcher = {
    attach(context) {
      once('language-switcher', '.language-switcher', context).forEach((switcher) => {
        const button = switcher.querySelector('.js-language-switcher-trigger');
        const dropdown = switcher.querySelector('.js-language-switcher-dropdown');
        button.addEventListener('click', (e) => {
          e.preventDefault();
          this.isOpen = !this.isOpen;
          dropdown.classList.toggle('show');
        })
      });
    },
  };

})(Drupal, once);
