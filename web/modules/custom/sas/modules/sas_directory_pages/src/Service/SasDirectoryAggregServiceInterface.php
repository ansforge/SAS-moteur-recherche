<?php

namespace Drupal\sas_directory_pages\Service;

/**
 * SasDirectoryAggregServiceInterface interface
 * provides helpers to call aggreg api.
 */
interface SasDirectoryAggregServiceInterface {

  /**
   * Get the practitioner actions & slots.
   *
   * @param array $placesList
   *   The PS places info array.
   *
   * @return array
   *   The action for existing places and eventually new places.
   */
  public function getPractitionerSlots($placesList);

}
