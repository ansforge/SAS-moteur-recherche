<?php

namespace Drupal\sas_geolocation\Service;

use Drupal\sas_api_client\Plugin\ClientEndpointPluginManager;
use Drupal\sas_geolocation\Model\SasLocation;

/**
 * Class SasGeolocationHelper.
 *
 * Define geolocation helper service.
 *
 * @package Drupal\sas_geolocation\Service
 */
class SasGeolocationHelper implements SasGeolocationHelperInterface {

  public const COORDINATES_PATTERN = '%s,%s';

  /**
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected ClientEndpointPluginManager $apiManager;

  /**
   * SasGeolocationHelper constructor.
   *
   * @param \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager $api_manager
   *   SAS API client plugin api manager.
   */
  public function __construct(
    ClientEndpointPluginManager $api_manager
  ) {
    $this->apiManager = $api_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function getPlaceLocation(string $place): ?SasLocation {

    $geoloc_result = $this->apiManager->sas_api(
      'address_localize',
      [
        'query' => [
          'searchValue' => $place,
        ],
      ]
    );

    if (empty($geoloc_result)) {
      return NULL;
    }
    try {
      // Create and return SasLocation object.
      return SasLocation::create($geoloc_result);
    }
    catch (\Exception $exception) {
      return NULL;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getSearchLocationDetails(SasLocation $location): array {
    return [
      'type' => $location->getType(),
      'lat_center' => $location->getLatitude(),
      'lon_center' => $location->getLongitude(),
      'center' => sprintf(
        self::COORDINATES_PATTERN,
        $location->getLatitude(),
        $location->getLongitude()
      ),
      'searchRadius' => $location->getDefaultRadius(),
      'county_code' => $location?->getCountyCode(),
      // county_list not manage on MVP. Waiting for region management.
      'county_list' => NULL,
      'city' => $location?->getCity(),
      'postal_code' => $location?->getPostCode(),
      'insee_code' => $location?->getInseeCode(),
    ];
  }

}
