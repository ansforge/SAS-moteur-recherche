<?php

namespace Drupal\sas_directory_pages\Entity\Feature;

/**
 * SasDirectoryAggregServiceInterface interface
 * provides helpers to call aggreg api.
 */
interface AggregatorLinkInterface {

  /**
   * Check if the practitioner exists in the aggregator api.
   *
   * @return bool|null
   *   TRUE if in the aggregator database.
   *   FALSE if not int the aggregator database.
   *   null if we could not determine.
   */
  public function isAggregPractitionerExist();

  /**
   * Get PS identifier for use with aggregator API.
   *
   * @return string|null
   *   the pro_id to use to interact with the aggreg api.
   */
  public function getAggregPsProId();

}
