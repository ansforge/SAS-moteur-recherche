(function ($,Drupal, drupalSettings, once) {
  "use strict";

  const isMobile = (window.innerWidth < 1400) ? true : false;

  Drupal.behaviors.user_account = {
    attach: function (context, settings) {
      once('user_account', '.account-panel', context).forEach( function (element) {
        const _layer = element;
        const btnYourAccount = document.getElementById('js-account-panel-opener');
        const btnTarget = document.getElementById(btnYourAccount.getAttribute('aria-controls'));
        const btnCloseYourAccount = document.getElementById('js-btn-close-user-account');
        const overlayYourAccount = document.getElementById('js-user-account-overlay');

        btnYourAccount.onclick = function (){
          if (this.classList.contains('btn-layer-opened')) {
            closeLayer();
          }else {
            btnTarget.classList.add('layer-show');
            this.setAttribute('aria-expanded', 'true');
            document.body.classList.add('modal-open');

            if(isMobile) {
              this.classList.add('btn-layer-opened');
            }

            setTimeout(function() {
              btnTarget.classList.add('open-animation');
            },300);

          }
        };

        const closeLayer = function () {
          btnTarget.classList.remove('open-animation');
          setTimeout(function () {
            btnTarget.classList.remove('layer-show');
            btnYourAccount.setAttribute('aria-expanded', 'false');
            btnYourAccount.classList.remove('btn-layer-opened');
            document.body.classList.remove('modal-open');
            btnYourAccount.focus();
          }, 200);

        };

        btnCloseYourAccount.onclick = function (){
          closeLayer();
        };

        overlayYourAccount.onclick = function (){
          closeLayer();
        };

        _layer.addEventListener('keydown', function (e) {
          if (e.code === 'Escape') {
            closeLayer();
          } else if (e.code === 'Tab') {
            if (e.shiftKey) {
              // shift + tab
              if (document.activeElement.className === 'firstfocusmodal') {
                document.activeElement.closest('.wrapper-user-account').querySelector('.lastfocusmodal').focus();
                e.preventDefault();
              }
            } else {
              // tab
              if (document.activeElement.className === 'lastfocusmodal') {
                document.activeElement.closest('.wrapper-user-account').querySelector('.firstfocusmodal').focus();
                e.preventDefault();
              }
            }
          }
        });
      });
    }
  };
} (jQuery,Drupal, drupalSettings, once));

