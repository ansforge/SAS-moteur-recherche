<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "create_territory",
 *   label = @Translation("SAS-API Update Territory endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/territory",
 *   api_user = "write",
 *   method = "POST"
 * )
 */
class CreateTerritory extends AbstractSasClientPluginBase {

}
