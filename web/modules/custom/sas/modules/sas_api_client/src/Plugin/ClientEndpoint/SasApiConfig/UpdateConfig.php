<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApiConfig;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "update_config",
 *   label = @Translation("CONFIG-API Update Config endpoint"),
 *   category = "sas_api_config",
 *   endpoint = "/{version}/config/{id}",
 *   api_user = "write",
 *   method = "PUT",
 *   body = {
 *     "group_name": NULL,
 *     "value": {},
 *   }
 * )
 */
class UpdateConfig extends AbstractSasClientPluginBase {

}
