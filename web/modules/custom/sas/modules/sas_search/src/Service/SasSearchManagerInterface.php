<?php

namespace Drupal\sas_search\Service;

/**
 * Interface SasSearchManagerInterface.
 *
 * Provide structure for Sas search manager.
 *
 * @package Drupal\sas_search\Service
 */
interface SasSearchManagerInterface {

  /**
   * Build solr query.
   *
   * @return mixed
   *   Query result or validation error.
   */
  public function makeSearch();

  /**
   * Prepare search for pref doctor.
   *
   * @return mixed
   *   Query result or validation error.
   */
  public function makePreferredDoctorSearch();

  /**
   * Build solr query for pref doctor.
   *
   * @return mixed
   *   Query result or validation error.
   */
  public function makePreferredDoctorQuery();

}
