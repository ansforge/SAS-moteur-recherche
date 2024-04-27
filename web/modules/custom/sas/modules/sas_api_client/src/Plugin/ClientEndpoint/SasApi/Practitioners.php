<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "get_ps_with_slots",
 *   label = @Translation("SAS-API GetPsWithSlots endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/practitioners",
 *   method = "POST",
 *   query = {
 *    "has_snp": NULL,
 *   }
 * )
 */
class Practitioners extends AbstractSasClientPluginBase {

}
