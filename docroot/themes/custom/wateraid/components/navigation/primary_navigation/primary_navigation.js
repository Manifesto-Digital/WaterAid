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

  Drupal.behaviors.primaryNavigation = {
    state: null,

    initNav(nav, context) {
      const header = document.querySelector('.js-site-header');
      const megaNav = nav.querySelector('.meganav');
      const mobileNav = nav.querySelector('.mobile-nav');
      const menuButton = document.querySelector('.js-menu-open-close-btn');
      const meganavPanels = megaNav.querySelectorAll('[data-meganav-panel-id]');

      const level2Triggers = nav.querySelectorAll('[data-level2-trigger]');
      const level3Triggers = nav.querySelectorAll('[data-level3-trigger]');

      // Get the meganav open/close transition time from CSS var, make it
      // unitless (nb. Must be in milliseconds).
      const meganavTransitionTimeString = getComputedStyle(megaNav).getPropertyValue('--meganav-transition-time');
      const meganavTransitionTime = parseInt(meganavTransitionTimeString.replace('ms', ''));


      menuButton.addEventListener('click', (e) => {
        this.state.isOpen = !this.state.isOpen;
      });

      level2Triggers.forEach((el) => {
        el.addEventListener('click', (e) => {
          e.preventDefault();
          const isMeganavTrigger = megaNav.contains(e.target);
          const triggerId = e.target.dataset.level2Trigger;
          console.log(triggerId);
          this.state.activeLevel2 = isMeganavTrigger && this.state.activeLevel2 === triggerId ? null : triggerId;
        });
      });
      level3Triggers.forEach((el) => {
        el.addEventListener('click', (e) => {
          e.preventDefault();
          const isMeganavTrigger = megaNav.contains(e.target);
          const triggerId = e.target.dataset.level3Trigger;
          this.state.activeLevel3 = isMeganavTrigger && this.state.activeLevel3 === triggerId ? null : triggerId;
        });
      });

      // Initialize subscribers, listen to property changes and react
      // accordingly.
      this.state = initSubscribers(
        {
          isOpen: false,
          activeLevel2: null,
          activeLevel3: null,
        },
        {
          // Listener for isOpen property changes, this is mobile menu only.
          isOpen: [
            (newValue, oldValue) => {
             if (newValue !== oldValue) {
               // menuButton.setAttribute('aria-expanded', newValue);
               document.body.classList.toggle('primary-menu-open');
             }
            }
          ],

          // Listener for activeLevel2 property change.
          activeLevel2: [
            (newValue, oldValue) => {
              nav.classList.add('meganav-animating');

              meganavPanels.forEach((el) => {
                el.classList.remove('active');
                el.setAttribute('aria-expanded', false);
              });
              level2Triggers.forEach((el) => {
                el.classList.remove('active');
              });

              if (newValue) {
                document.body.classList.add('primary-menu-open');
                // Changing meganav panel.
                const newPanel = megaNav.querySelector('[data-meganav-panel-id="' + newValue + '"]');
                newPanel.classList.add('active', 'panel-animating-in');
                newPanel.setAttribute('aria-expanded', true);
                // Active class on level 1 nav button.
                nav.querySelectorAll('[data-level2-trigger="' + newValue + '"]').forEach((el) => {
                  el.classList.add('active');
                });
              }
              else {
                // Closing meganav.
                document.body.classList.remove('primary-menu-open');
                this.state.activeLevel3 = null;
              }

              setTimeout(() => { meganavPanels.forEach((el) => {
                // Remove any 'animating' class flags after a timeout that
                // matches with the menu transition time.
                nav.querySelectorAll('[data-meganav-panel-id]').forEach((el) => { el.classList.remove('panel-animating-in'); });
                nav.classList.remove('meganav-animating');
              })}, meganavTransitionTime)
            }
          ],

          // Listener for activeLevel3 property change.
          activeLevel3: [
            (newValue, oldValue) => {

              nav.querySelectorAll('[data-level3-trigger]').forEach((el) => { el.classList.remove('active'); });
              nav.querySelectorAll('[data-level-3-id]').forEach((el) => {
                el.classList.remove('show');
                el.setAttribute('aria-expanded', false);
              });

              if (newValue !== oldValue && newValue !== null) {
                nav.querySelector('[data-level3-trigger="' + newValue + '"]').classList.add('active');
                const elementToOpen = nav.querySelector('[data-level-3-id="' + newValue + '"]');
                elementToOpen.classList.add('show');
                elementToOpen.setAttribute('aria-expanded', true);
              }
            }
          ]
        }
      );
      // Attach it to the Drupal global to make it globally available.
      Drupal.primaryMenuState = this.state;
    },

    attach(context) {
      once('primary-navigation-menus', '.primary-navigation').forEach((el) => {
        this.initNav(el, context);
      });
    },
  };

})(Drupal, once);
