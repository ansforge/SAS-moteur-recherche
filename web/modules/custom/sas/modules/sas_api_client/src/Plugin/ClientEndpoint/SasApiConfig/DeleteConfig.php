<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApiConfig;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "delete_config",
 *   label = @Translation("CONFIG-API Delete Config endpoint"),
 *   category = "sas_api_config",
 *   endpoint = "/{version}/config/{id}",
 *   api_user = "write",
 *   method = "DELETE"
 * )
 */
class DeleteConfig extends AbstractSasClientPluginBase {

}
