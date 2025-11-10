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
      const meganavPanels = megaNav.querySelectorAll('[data-meganav-panel-id]');

      const mobileNav = nav.querySelector('.mobile-nav');
      const menuButton = document.querySelector('.js-menu-open-close-btn');
      const mobileNavBackButtons = document.querySelectorAll('.js-mobile-nav-back-btn');

      const level2Triggers = nav.querySelectorAll('[data-level2-trigger]');
      const level3Triggers = nav.querySelectorAll('[data-level3-trigger]');

      // Get the meganav open/close transition time from CSS var, make it
      // unit-less (nb. Must be in milliseconds).
      const meganavTransitionTimeString = getComputedStyle(megaNav).getPropertyValue('--meganav-transition-time');
      const meganavTransitionTime = parseInt(meganavTransitionTimeString.replace('ms', ''));
      const mobileNavTransitionTimeString = getComputedStyle(mobileNav).getPropertyValue('--mobile-nav-transition-time');
      const mobileNavTransitionTime = parseInt(mobileNavTransitionTimeString.replace('ms', ''));


      menuButton.addEventListener('click', (e) => {
        this.state.isOpen = !this.state.isOpen;
      });

      level2Triggers.forEach((el) => {
        el.addEventListener('click', (e) => {
          e.preventDefault();
          const isMeganavTrigger = megaNav.contains(e.target);
          const triggerId = e.target.dataset.level2Trigger;
          if (isMeganavTrigger) {
            if (this.state.activeLevel2 === triggerId) {
              this.state.isOpen = false;
              this.state.activeLevel2 = null;
            }
            else {
              this.state.activeLevel2 = triggerId;
            }
          }
          else {
            this.state.activeLevel2 = triggerId;
          }
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

      mobileNavBackButtons.forEach((el) => {
        el.addEventListener('click', (e) => {
          e.preventDefault();
          if (this.state.activeLevel3 !== null) {
            this.state.activeLevel3 = null;
          }
          else if (this.state.activeLevel2 !== null) {
            this.state.activeLevel2 = null;
          }
        });
      });

      document.addEventListener('click', (e) => {
        const target = e.target;
        if (this.state.isOpen && !header.contains(target)) {
          this.state.isOpen = false;
        }
      });

      // Open the navigation.
      this.openNav = () => {
        menuButton.setAttribute('aria-expanded', 'true');
        document.body.classList.add('primary-menu-open');
      }
      // Close the navigation.
      this.closeNav = () => {
        document.body.classList.remove('primary-menu-open');
        menuButton.setAttribute('aria-expanded', 'false');
        mobileNav.classList.remove('mobile-nav-static');
        this.state.activeLevel2 = null;
        this.state.activeLevel3 = null;
      }

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
              if (newValue) {
                this.openNav();
              }
              else {
                this.closeNav();
              }
             }
          ],

          // Listener for activeLevel2 property change.
          activeLevel2: [
            (newValue, oldValue) => {
              this.state.activeLevel3 = null;
              // If the new level isn't numeric, set the menu level active to
              // the new value.
              header.setAttribute('data-mobile-menu-level-active', isNaN(newValue) ? newValue : '2');
              megaNav.classList.add('meganav-animating');
              mobileNav.classList.remove('mobile-nav-static');

              meganavPanels.forEach((el) => {
                el.classList.remove('active');
                el.setAttribute('aria-expanded', false);
              });
              level2Triggers.forEach((el) => {
                el.classList.remove('active');
              });

              if (newValue) {
                //this.state.isOpen = true;
                document.body.classList.add('primary-menu-open');
                // Changing meganav panel.
                const newPanel = megaNav.querySelector('[data-meganav-panel-id="' + newValue + '"]');
                if (newPanel) {
                  newPanel.classList.add('active', 'panel-animating-in');
                  newPanel.setAttribute('aria-expanded', true);
                }
                // Active class on level 1 nav button.
                nav.querySelectorAll('[data-level2-trigger="' + newValue + '"]').forEach((el) => {
                  el.classList.add('active');
                });
                mobileNav.querySelector('[data-mobile-submenu-id="' + newValue + '"]').classList.add('active');
              }
              else {
                // Closing meganav.
                header.setAttribute('data-mobile-menu-level-active', '1');
              }

              // MegaNav is animating timeout.
              setTimeout(() => { meganavPanels.forEach((el) => {
                // Remove any 'animating' class flags after a timeout period
                // that matches with the menu CSS transition time.
                megaNav.querySelectorAll('[data-meganav-panel-id]').forEach((el) => { el.classList.remove('panel-animating-in'); });
                megaNav.classList.remove('meganav-animating');
              })}, meganavTransitionTime)

              // Mobile nav is animating timeout.
              setTimeout(() => {
                // Add a class to prevent transition animations outside of
                // intentional menu animations.
                mobileNav.classList.add('mobile-nav-static');
                if (newValue === null) {
                  mobileNav.querySelectorAll('[data-mobile-submenu-id]').forEach((el) => { el.classList.remove('active'); });
                }
              }, mobileNavTransitionTime)
            }
          ],

          // Listener for activeLevel3 property change.
          activeLevel3: [
            (newValue, oldValue) => {
              mobileNav.classList.remove('mobile-nav-static');

              nav.querySelectorAll('[data-level3-trigger]').forEach((el) => { el.classList.remove('active'); });
              nav.querySelectorAll('[data-level-3-id]').forEach((el) => {
                el.classList.remove('show');
                el.setAttribute('aria-expanded', false);
              });

              if (newValue !== oldValue) {
                if (newValue !== null) {
                  //this.state.isOpen = true;

                  nav.querySelector('[data-level3-trigger="' + newValue + '"]').classList.add('active');
                  const elementToOpen = nav.querySelector('[data-level-3-id="' + newValue + '"]');
                  elementToOpen.classList.add('show');
                  elementToOpen.setAttribute('aria-expanded', true);

                  // Mobile nav, make corresponding 3rd level menu active.
                  mobileNav
                    .querySelector('[data-mobile-submenu-id="'+ this.state.activeLevel2 +'"]')
                    .querySelector('[data-mobile-submenu-id="'+ this.state.activeLevel3 +'"]')
                    .classList.add('active');

                  header.setAttribute('data-mobile-menu-level-active', '3');
                }
                else {
                  header.setAttribute('data-mobile-menu-level-active', '2');
                }

                // Mobile nav is animating timeout.
                setTimeout(() => {
                  mobileNav.classList.add('mobile-nav-static');
                }, mobileNavTransitionTime)
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
