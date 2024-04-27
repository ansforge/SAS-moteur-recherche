(function (Drupal, drupalSettings, once) {
  Drupal.behaviors.sas_welcome_popin = {
    attach: function (context, settings) {
      once('sas_welcome_popin', '.sas-welcome-popin', context).forEach(function (element) {
        Drupal.dialog(document.getElementById('sas-welcome-popin'), {width: '90%', height: 'auto', dialogClass: 'dialog-sas-welcome-popin'}).show();

        const dialog = document.querySelector('.dialog-sas-welcome-popin');
        const close = dialog.querySelector('.ui-dialog-titlebar-close');
        const wrapper = document.createElement('div');
        const overlay = document.createElement('div');
        wrapper.classList.add('wrapper-dialog-sas-welcome-popin');
        overlay.classList.add('dialog-overlay');
        dialog.parentNode.insertBefore(wrapper, dialog);
        wrapper.appendChild(dialog);
        wrapper.appendChild(overlay);

        const closePopin = function () {
          wrapper.remove();
        };

        close.onclick = function () {
          closePopin();
        };

        dialog.addEventListener('keydown', function (e) {
          if (e.code === 'Escape') {
            closePopin();
          }
        });
      });
    }
  };
} (Drupal, drupalSettings, once));
