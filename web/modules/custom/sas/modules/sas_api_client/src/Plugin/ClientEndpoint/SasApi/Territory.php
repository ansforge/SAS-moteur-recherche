<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "get_territory",
 *   label = @Translation("SAS-API Territory endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/territory",
 *   method = "GET",
 *   exposed = TRUE
 * )
 */
class Territory extends AbstractSasClientPluginBase {

}
