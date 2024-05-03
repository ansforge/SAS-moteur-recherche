<?php

namespace Drupal\sas_search\Service;

use Drupal\sas_search\SasSolrQueryInterface;

/**
 * Interface SasSolrQueryManagerInterface.
 *
 * Provides structure for SAS solr query manager.
 */
interface SasSolrQueryManagerInterface extends SasSolrQueryInterface {

  /**
   * Get search type depending on the search "what".
   *
   * @return string
   *   Search type. Default to 'normal'.
   */
  public function getSearchType();

}
