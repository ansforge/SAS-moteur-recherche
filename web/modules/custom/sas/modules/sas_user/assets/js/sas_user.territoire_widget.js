(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.entityReferenceTerritoires = {
    attach: function (context, settings) {
      $(once('sas_territoires_autocomplete', '.sas-territoire-autocomplete-wrapper:not(.territoire-processed)', context)).each(function () {
        $(this).addClass('territoire-processed');
        $('.field-label .label', $(this)).append('&nbsp;-&nbsp;');
        $('.sas-territoire-autocomplete-select', $(this)).appendTo($('.field-label', $(this)));
      });
    }
  };

})(jQuery, Drupal);
