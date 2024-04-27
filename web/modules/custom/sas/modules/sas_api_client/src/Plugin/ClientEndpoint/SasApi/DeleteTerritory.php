<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "delete_territory",
 *   label = @Translation("SAS-API Delete Territory endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/territory/{id}",
 *   api_user = "write",
 *   method = "DELETE"
 * )
 */
class DeleteTerritory extends AbstractSasClientPluginBase {

}
