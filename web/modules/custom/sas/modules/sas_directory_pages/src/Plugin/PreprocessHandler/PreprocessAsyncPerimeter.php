<?php

namespace Drupal\sas_directory_pages\Plugin\PreprocessHandler;

use Drupal\sante_directory_pages\Plugin\PreprocessHandlerBase;

/**
 * Preprocessing PS aync load use case
 * to be able to wait for external/slow api response (ex aggregator)
 *
 * @package Drupal\sas_directory_pages\Plugin\PreprocessHandler
 *
 * @PreprocessHandler(
 *  id = "preprocess_async_perimeter",
 *  label = @Translation("Preprocess async perimeter"),
 *  bundles = {
 *    "professionnel_de_sante",
 *  },
 *  themes = {
 *    "annuaire_professionnel_de_sante"
 *  },
 *  context = "sas",
 *  priority = 900
 * )
 */
class PreprocessAsyncPerimeter extends PreprocessHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (isset($this->context['is_interfaced_aggregator']) && $this->context['is_interfaced_aggregator'] === TRUE) {
      // Please note: return here to disable async
      // content load and facilitate debug/dev
      // even if the PS is interfaced with aggregator.
      // This will allow dump/expose that would not work in ajax context.
      // return;
      // ---------
      // ⚠️ We need to preload the sas lib later needed in the async loaded content
      // Cf. issue exlained in web/modules/custom/sas/modules/sas_vuejs/sas_vuejs.libraries.yml.
      $this->variables["#attached"]["library"][] = 'sas_vuejs/aggreg-ps-calendar';
      // ---------
      $this->variables['directory_page_async_perimeter'] = TRUE;
      $directory_page_content_ajax = $this->variables["elements"]["directory_page_content_ajax"] ?? FALSE;
      if (!$directory_page_content_ajax) {
        // When not in the ajax context
        // we attach the library to trigger async content load.
        $this->variables["#attached"]["library"][] = 'sas_directory_pages/directory_page_content_ajax';
        // We stop further preprocessing.
        return FALSE;
      }
    }

  }

}
