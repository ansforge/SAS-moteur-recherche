<?php

namespace Drupal\sas_directory_pages\Plugin\PreprocessHandler;

use Drupal\sante_directory_pages\Plugin\PreprocessHandlerBase;

/**
 * Preprocessing PS data for temporary loader content.
 *
 * @package Drupal\sas_directory_pages\Plugin\PreprocessHandler
 *
 * @PreprocessHandler(
 *  id = "preprocess_async_loader_content",
 *  label = @Translation("Preprocess async loader content"),
 *  bundles = {
 *    "professionnel_de_sante",
 *  },
 *  themes = {
 *    "annuaire_professionnel_de_sante"
 *  },
 *  context = "sas",
 *  priority = 950
 * )
 */
class PreprocessAsyncLoader extends PreprocessHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (isset($this->context['is_interfaced_aggregator']) && $this->context['is_interfaced_aggregator'] === TRUE) {
      $directory_page_content_ajax = $this->variables["elements"]["directory_page_content_ajax"] ?? FALSE;
      if (!$directory_page_content_ajax) {
        // We need to preprocess minimal infos for the loader content,
        // because in this context PreprocessNodeFullProfessionnelDeSante
        // will be bypassed.
        $this->variables['rpps'] = $this->getEntityAttribute('field_identifiant_rpps');
        $this->variables['prof'] = $this->node->sgpSiteGetJobTitle();
      }
    }

  }

}
