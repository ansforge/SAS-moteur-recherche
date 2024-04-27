(function ($, Drupal, drupalSettings, once) {
  "use strict";
  Drupal.behaviors.floatlabelsas = {
    attach: function (context, drupalSettings) {
      once('floatlabelsas','.float-label-item', context).forEach( function (element) {
        const showClass = "show-label";
        const $elm = element;
        const $label = $($elm).find("label");
        const $this = $($elm).find("input, select, textarea");

        $label.addClass('float-label');
        if ($this.hasClass('required')) {
          $label.html($label.html() + ' <span class="required"><span class="sr-only">Champ obligatoire</span><span aria-hidden="true">*</span></span>');
        }

        $this.bind("checkval", function () {
          if ($this.val() !== "" && $this.val() !== "aucun" && $this.val() !== null) {
            $label.addClass(showClass);
          } else {
            $label.removeClass(showClass);
          }
        });

        $this.on("keyup textchange change", function () {
          $this.trigger("checkval");
        });

        $this.trigger("checkval");
      });
    }
  };
  Drupal.behaviors.showPasswords = {
    /**
     * Code dupliqué du fichier form du theme santefr
     * @see web/themes/custom/santefr/js/custom/forms.js
     */

    attach: function (context, drupalSettings) {
      once('showPasswords','.js-form-type-password', context).forEach( function (element) {
        let timerId;
        $(element).find('input')
          .on('focus', function () {
            const $btnShow = $(this).parent().find('.show-password');
            $btnShow.removeClass('hide-button');
            clearTimeout(timerId);
          })
          .on('blur', function () {
            timerId = setTimeout(function() {$(this).next().addClass('hide-button');}.bind(this), 1000);
          })
          .on('click', function () {
            const $btnShow = $(this).parent().find('.show-password');
            $btnShow.show();
          });
        $(element).find('.show-password').on('click', function (e) {
          e.preventDefault();
          const $input = $(this).prev();
          const $sronly = $(this).find('.sr-only');
          const $icon = $(this).find('i');
          if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $sronly.text('Cacher mon mot de passe');
            $icon.removeClass('icon-password-visible').addClass('icon-password');
          } else {
            $input.attr('type', 'password');
            $sronly.text('Afficher le mot de passe en clair');
            $icon.removeClass('icon-password').addClass('icon-password-visible');
          }
          $input.focus();
        });
      });
    }
  };
} (jQuery, Drupal, drupalSettings, once));

