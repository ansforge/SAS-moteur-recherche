<?php

namespace Drupal\sas_search_index\Service;

/**
 * Interface SasSearchIndexHelperInterface.
 *
 * Skeleton of SAS search indexation helper.
 *
 * @package Drupal\sas_search_index\Service
 */
interface SasSearchIndexHelperInterface {

  /**
   * Force indexation of given node.
   *
   * @param int $nid
   *   Node id.
   */
  public function indexSpecificItem(int $nid);

}
