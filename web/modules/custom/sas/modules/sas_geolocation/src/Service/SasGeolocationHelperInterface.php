<?php

namespace Drupal\sas_geolocation\Service;

use Drupal\sas_geolocation\Model\SasLocation;

/**
 * Interface SasGeolocationHelperInterface.
 *
 * Provide structure for geolocation helper service.
 *
 * @package Drupal\sas_geolocation\Service
 */
interface SasGeolocationHelperInterface {

  /**
   * Get location for a given place name.
   *
   * Use mapbox call to get geolocation data and build SAS location object.
   *
   * @param string $place
   *
   * @return \Drupal\sas_geolocation\Model\SasLocation|null
   */
  public function getPlaceLocation(string $place): ?SasLocation;

  /**
   * Get detailed location data as array.
   *
   * @return array
   *   Location data.
   */
  public function getSearchLocationDetails(SasLocation $location): array;

}
