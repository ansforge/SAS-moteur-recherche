<?php

namespace Drupal\sas_directory_pages\Service;

use Drupal\sas_api_client\Plugin\ClientEndpointPluginManager;

/**
 * Class SasDirectoryAggregService provides helpers to call aggreg api.
 */
class SasDirectoryAggregService implements SasDirectoryAggregServiceInterface {

  /**
   * The ClientEndpointPluginManager service.
   *
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  private $sasClient;

  /**
   * Undocumented function.
   *
   * @param \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager $sas_client
   */
  public function __construct(ClientEndpointPluginManager $sas_client) {
    $this->sasClient = $sas_client;
  }

  /**
   * {@inheritdoc}
   */
  public function getPractitionerSlots($placesList) {
    return $this->sasClient->aggregator('practitioner_slots', [
      // The endpoint needs a specific structure: [{nid: { ... }, nid: { ... }}].
      "body" => [$placesList],
    ]);
  }

}
