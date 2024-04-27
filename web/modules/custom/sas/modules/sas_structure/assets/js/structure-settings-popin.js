(function ($) {
  'use strict';

  /**
   * @description Check the form validation
   */
  function formValidation() {
    let isValid = true;

    const $contentModal = $(".modal-sas");
    const participationSasIsChecked = $contentModal.find(".js-form-item-sas-participation input").is(':checked');
    const declarationIsChecked = $contentModal.find(".js-form-item-hours-available input").is(':checked');

    if (
      participationSasIsChecked &&
      !declarationIsChecked
    ) {
      isValid = false;
    }

    const $submitEl = $contentModal.find(".wrapper-btn-actions .js-form-submit");

    if (!isValid) {
      $submitEl.prop("disabled", true);
    } else {
      $submitEl.removeAttr("disabled");
    }
  }

  Drupal.behaviors.structureSettingPopin = {
    attach: function (context, settings) {
      $(once('structure-settings-popin', '#drupal-modal', context)).each(function () {
        setTimeout(function () {
          const $contentModal = $(".modal-sas");

          const $participationSasCheckbox = $contentModal.find(".js-form-item-sas-participation");
          const $declarationCheckBoxEl = $contentModal.find('.js-form-item-hours-available');

          $participationSasCheckbox.click(function() {
            setTimeout(() => formValidation());
          });

          $declarationCheckBoxEl.click(function() {
            setTimeout(() => formValidation());
          });

          formValidation();
        }, 500);
      });
    },
  };
}(jQuery));
