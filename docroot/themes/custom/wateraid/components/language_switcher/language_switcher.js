(function(Drupal, once) {

  Drupal.behaviors.languageSwitcher = {
    attach(context) {
      // console.dir(context)
      once('language-switcher', '.language-switcher', context).forEach((switcher) => {
        const link = switcher.querySelector('a');
        const select = switcher.querySelector('select');
        link.addEventListener('click', (e) => {
          // console.log('click');
          e.preventDefault();
          select.dispatchEvent(new Event('click'));
        })
        select.addEventListener('change', (e) => {
          // console.log('change');
          const el = e.target;
          // console.log(e.target);
        });
      });
    },
  };

})(Drupal, once);
