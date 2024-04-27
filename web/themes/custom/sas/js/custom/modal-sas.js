(function ($,Drupal, drupalSettings, once) {
  "use strict";

  Drupal.behaviors.modal_sas = {
    attach: function (context, settings) {

      function handleModal(_element, hasObserver, observer) {
        const _this = _element;
        const _popin = document.querySelector('.ui-dialog');
        const _close = document.querySelector('.ui-dialog-titlebar-close');
        const _btnCancel = document.querySelector('.js-btn-cancel');

        const closePopin = () => {
          document.body.classList.remove('modal-open');
          _this.focus();
          if (hasObserver) observer.disconnect();
        };

        document.body.classList.add('modal-open');

        if (_close) {
          _close.onclick = closePopin;
          // _close.focus();
        }

        if (document.querySelectorAll('.ui-widget-overlay').length > 0) {
          document.querySelector('.ui-widget-overlay').onclick = () => (_close.click());
        }

        if (document.querySelectorAll('.modal-backdrop').length > 0) {
          document.querySelector('.modal-backdrop').onclick = () => (_close.click());
        }

        if (document.querySelector('.form-submit')) {
          document.querySelector('.form-submit').onclick = () => (closePopin());
        }

        if (_btnCancel) {
          _btnCancel.onclick = () => (_close.click());
        }

        if (_popin) {
          _popin.setAttribute('aria-modal', true);
          _popin.addEventListener('keydown', function (e) {
            if (e.code === 'Escape') {
              _close.click();
              if (hasObserver) observer.disconnect();
            } else if (e.code === 'Tab') {
              if (e.shiftKey) {
                // shift + tab
                if (document.activeElement.className === 'firstfocusmodal') {
                  document.activeElement.closest('.vuemodal-sas').querySelector('.lastfocusmodal').focus();
                  e.preventDefault();
                }
              } else {
                // tab
                if (document.activeElement.className === 'lastfocusmodal') {
                  document.activeElement.closest('.vuemodal-sas').querySelector('.firstfocusmodal').focus();
                  e.preventDefault();
                }
              }
            }
          });
        }
      }

      once('modal_sas', '.js-btn-open-modal-sas', context).forEach((_element) => {
        _element.onclick = () => {
          const calenderPage =  window.drupalSettings?.sas_vuejs?.parameters;

          if (calenderPage) {
            handleModal(_element, false);
          }
          else {
            // handle element after it is rendered
            const observer = new MutationObserver(() => {
              handleModal(_element, true, observer);
            });

            // observe document body for changes
            observer.observe(document.body, {
              childList: true,
              subtree: true,
            });
          }
        };
      });
    }
  };
} (jQuery, Drupal, drupalSettings, once));

