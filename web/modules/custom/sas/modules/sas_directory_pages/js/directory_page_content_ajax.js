(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.directory_page_content_ajax = {
    attach: function (context) {
      $(once('ajax-query-started', '#sas-directory-page-content-ajax-placeholder', context)).each(function () {
        const queryParams = new Proxy(new URLSearchParams(window.location.search), {
          get: (searchParams, prop) => searchParams.get(prop),
        });
          const nid = $(this).data('nid');
          if (!nid) {
            console.error("Found #sas-directory-page-content-ajax-placeholder but no nid.")
            return;
          }
          let params = '';
          if (queryParams.location_id) {
            params = '?location_id=' + queryParams.location_id;
          }
          Drupal.ajax({ url: '/sas_directory_pages/' + nid + params }).execute();
        });
    }
  };

})(jQuery, Drupal);
