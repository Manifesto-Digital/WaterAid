(function(Drupal, once) {

  /**
   * Create reactive state object using Proxy, to provide an event listener
   * implementation for the nav properties.
   */
  function initSubscribers(initialState, listeners = {}) {
    return new Proxy(initialState, {
      set(target, property, value) {
        const oldValue = target[property];
        target[property] = value;

        // Trigger listeners for the property.
        if (listeners[property]) {
          listeners[property].forEach(callback => {
            callback(value, oldValue);
          });
        }
        return true;
      }
    });
  }

  // Behavior for the desktop version of the utility menu.
  Drupal.behaviors.utilityNavigation = {
    state: null,

    initUtilityNav(utilityNav, context) {
      const body = document.body;
      const siteSelectorBtn = document.querySelector('.js-site-selector-btn');
      const siteSelector = document.querySelector('.js-site-selector-panel .site-selector');

      // Get the meganav open/close transition time from CSS var, make it
      // unit-less (nb. Must be in milliseconds).
      const utilityNavTransitionTimeString = getComputedStyle(siteSelector).getPropertyValue('--site-selector-transition-time');
      const utilityNavTransitionTime = parseInt(utilityNavTransitionTimeString.replace('ms', ''));

      siteSelectorBtn.addEventListener('click', (e) => {
        this.state.isOpen = !this.state.isOpen;
      });

      document.addEventListener('click', (e) => {
        const target = e.target;
        if (this.state.isOpen && !utilityNav.contains(target)) {
          this.state.isOpen = false;
        }
      });

      // Open the navigation.
      this.openNav = () => {
        siteSelectorBtn.setAttribute('aria-expanded', 'true');
        siteSelector.classList.add('active');
      }
      // Close the navigation.
      this.closeNav = () => {
        siteSelectorBtn.setAttribute('aria-expanded', 'false');
        siteSelector.classList.remove('active');
      }

      // Initialize subscribers, listen to property changes and react
      // accordingly.
      this.state = initSubscribers(
        {
          isOpen: false,
        },
        {
          isOpen: [
            (newValue, oldValue) => {
              if (newValue) {
                this.openNav();
              }
              else {
                this.closeNav();
              }
              body.classList.add('site-selector-panel-animating')
              setTimeout(() => {
                // Remove any 'animating' class flags after a timeout period
                // that matches with the menu CSS transition time.
                body.classList.remove('site-selector-panel-animating');
              }, utilityNavTransitionTime)
            }
          ],
        }
      );
      // Attach it to the Drupal global to make it globally available.
      Drupal.utilityMenuState = this.state;
    },

    attach(context) {
      once('utility-navigation', '.utility-navigation', context).forEach((el) => {
        this.initUtilityNav(el, context);
      });
    },
  };

})(Drupal, once);
