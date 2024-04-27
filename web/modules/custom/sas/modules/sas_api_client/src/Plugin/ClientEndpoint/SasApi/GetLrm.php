<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "get_lrm",
 *   label = @Translation("SAS-API GetLrm endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/repository/speciality/{id}",
 *   method = "GET",
 *   exposed = TRUE
 * )
 */
class GetLrm extends AbstractSasClientPluginBase {

}
