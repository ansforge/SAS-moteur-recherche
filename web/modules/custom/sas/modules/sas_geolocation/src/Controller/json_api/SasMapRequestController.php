<?php

namespace Drupal\sas_geolocation\Controller\json_api;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for SAS map routes.
 */
class SasMapRequestController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function getMapConfig() {
    $this->entityTypeManager = parent::entityTypeManager();
    $response = new CacheableJsonResponse();

    $config = $this->entityTypeManager->getStorage('config_pages')
      ->load('mapbox_maptiler');
    $response->addCacheableDependency($config);
    $maptilerEnabled = $config->field_switch_to_maptiler->value ? intval($config->field_switch_to_maptiler->value) : 0;
    $mapbox_provider = $this->entityTypeManager->getStorage('geocoder_provider')
      ->load('mapbox')
      ->get('configuration');
    $maptiler_provider = $this->entityTypeManager->getStorage('geocoder_provider')
      ->load('maptiler')
      ->get('configuration');

    $data = [
      'mapboxAccessToken' => $mapbox_provider["accessToken"],
      'santeMapboxMaptilerAccessToken' => $maptiler_provider["apiKey"],
      'santeMapboxEnableMaptiler' => $maptilerEnabled,
      'santeMapboxMaptilerLayerTileUrl' => $maptiler_provider["tileUrl"],
    ];

    $response->setData($data);
    $response->setEncodingOptions(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $response;
  }

}
