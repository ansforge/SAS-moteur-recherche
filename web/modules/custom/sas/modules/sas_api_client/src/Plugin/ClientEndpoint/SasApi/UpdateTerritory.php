<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "update_territory",
 *   label = @Translation("SAS-API Update Territory endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/territory/{id}",
 *   api_user = "write",
 *   method = "PUT"
 * )
 */
class UpdateTerritory extends AbstractSasClientPluginBase {

}
