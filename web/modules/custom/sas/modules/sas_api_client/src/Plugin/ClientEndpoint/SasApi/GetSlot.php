<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "get_slot",
 *   label = @Translation("SAS-API GetSlot endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/slot/{id}",
 *   method = "GET",
 *   exposed = TRUE
 * )
 */
class GetSlot extends AbstractSasClientPluginBase {

}
