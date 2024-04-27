(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.RolesOsnpIoaInfos = {
    attach: function (context) {
      setTimeout(function() {
        const btnValidateEl = $('#edit-role-change input');
        btnValidateEl.click(function (e) {
          const checkboxIoa = $('#edit-role-change-sas-ioa');
          const checkboxOsnp = $('#edit-role-change-sas-regulateur-osnp');
          const MsgEl = $('#notice-osnp-ioa');
          if (checkboxIoa[0].checked || checkboxOsnp[0].checked) {
            MsgEl.removeClass('hidden');
          } else{
            MsgEl.addClass('hidden');
          }
        });
      });
    }
  };

  Drupal.behaviors.RolesOsnpIoaInfosOnload = {
    attach: function (context, settings) {
      $(once('edit-role-change-input', '#edit-role-change', context)).each(function () {
        const checkboxIoa = $('#edit-role-change-sas-ioa');
        const checkboxOsnp = $('#edit-role-change-sas-regulateur-osnp');
        const MsgEl = $('#notice-osnp-ioa');
        if (checkboxIoa[0].checked || checkboxOsnp[0].checked) {
          MsgEl.removeClass('hidden');
        }
      });
    }
  };

})(jQuery, Drupal);
