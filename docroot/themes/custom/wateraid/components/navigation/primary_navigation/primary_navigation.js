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
      const menuButton = document.querySelector('.js-menu-open-close-btn');
      const meganavPanels = nav.querySelectorAll('[data-meganav-panel-id]');
      const level2Triggers = nav.querySelectorAll('[data-level2-trigger]');
      const level3Triggers = nav.querySelectorAll('[data-level3-trigger]');

      // Get the meganav open/close transition time from CSS var, make it
      // unitless (nb. Must be in milliseconds).
      const meganavTransitionTimeString = getComputedStyle(nav).getPropertyValue('--meganav-transition-time');
      const meganavTransitionTime = parseInt(meganavTransitionTimeString.replace('ms', ''));


      menuButton.addEventListener('click', (e) => {
        this.state.isOpen = !this.state.isOpen;
      });

      level2Triggers.forEach((el) => {
        el.addEventListener('click', (e) => {
          e.preventDefault();
          const trigger_id = e.target.dataset.level2Trigger;
          this.state.activeLevel2 = this.state.activeLevel2 === trigger_id ? null : trigger_id;
        });
      });
      level3Triggers.forEach((el) => {
        el.addEventListener('click', (e) => {
          e.preventDefault();
          const trigger_id = e.target.dataset.level3Trigger;
          this.state.activeLevel3 = this.state.activeLevel3 === trigger_id ? null : trigger_id;
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
               document.body.classList.toggle('meganav-open');
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
              level2Triggers.forEach((el) => { el.classList.remove('active'); });

              if (newValue) {
                document.body.classList.add('meganav-open');
                // Changing meganav panel.
                nav.querySelector('[data-meganav-panel-id="' + newValue + '"]').classList.add('active', 'panel-animating-in');
                nav.querySelector('[data-meganav-panel-id="' + newValue + '"]').setAttribute('aria-expanded', true);
                // Active class on level 1 nav button.
                nav.querySelector('[data-level2-trigger="' + newValue + '"]').classList.add('active');
              }
              else {
                // Closing meganav.
                document.body.classList.remove('meganav-open');
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
    },

    attach(context) {
      once('meganav', '.meganav').forEach((el) => {
        this.initNav(el, context);
      });
    },
  };

})(Drupal, once);
