(function (Drupal, once) {

  Drupal.wateraidModals = {
    modals: {},
    // Globalised helper to close all modals.
    closeAllModals: function() {
      Object.values(this.modals).forEach(function (modalInstance) {
        modalInstance.closeModal();
      });
    }
  };

  Drupal.behaviors.modal = {

    openModal(instanceId) {
      document.body.classList.add('modal-open');
      document.querySelector('[data-modal-instance-id="' + instanceId + '"]').classList.add('modal--open');
    },

    closeModal(instanceId) {
      document.body.classList.remove('modal-open');
      document.querySelector('[data-modal-instance-id="' + instanceId + '"]').classList.remove('modal--open');
    },


    attach(context) {
      once('modal-init', '.modal' , context).forEach((modal) => {
        const instanceId = modal.dataset.modalInstanceId;

        // Open modal click event handler. There can be multiple open
        // triggers.
        document.querySelectorAll('[data-modal-trigger-id="'+ instanceId + '"]').forEach((triggerEl) => {
          triggerEl.addEventListener('click', (e) => {
            e.preventDefault();
            this.openModal(instanceId);
          })
        });

        // Close modal click event handler. There can be multiple close
        // triggers.
        modal.querySelectorAll('[data-modal-close-id="'+ instanceId + '"]').forEach((closeEl) => {
          closeEl.addEventListener('click', (e) => {
            e.preventDefault();
            this.closeModal(instanceId);
          });
        });

        // Attach to the Drupal object for remote open / close.
        Drupal.wateraidModals.modals[instanceId] = {
          instanceId: instanceId,
          openModal: () => this.openModal(instanceId),
          closeModal: () => this.closeModal(instanceId),
        };
      })

    },


  };
})(Drupal, once);
