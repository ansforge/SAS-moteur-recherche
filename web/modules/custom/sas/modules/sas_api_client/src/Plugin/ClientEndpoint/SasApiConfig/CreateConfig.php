<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApiConfig;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "create_config",
 *   label = @Translation("CONFIG-API Create Config endpoint"),
 *   category = "sas_api_config",
 *   endpoint = "/{version}/config",
 *   api_user = "write",
 *   method = "POST",
 *   body = {
 *     "group_name": NULL,
 *     "name": NULL,
 *     "value": NULL,
 *   }
 * )
 */
class CreateConfig extends AbstractSasClientPluginBase {

}
