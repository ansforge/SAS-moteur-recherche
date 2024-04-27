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
   * Get pagination data from query parameters.
   *
   * @return mixed
   *   Array with page, item_per_page and offset data.
   */
  public function getPagination();

  /**
   * Get search type depending to the search "what".
   *
   * @return string
   *   Search type. Default to 'normal'.
   */
  public function getSearchType();

}
