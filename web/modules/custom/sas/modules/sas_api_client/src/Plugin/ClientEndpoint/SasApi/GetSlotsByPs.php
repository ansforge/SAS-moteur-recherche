<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "get_slots_by_ps",
 *   label = @Translation("SAS-API GetSlotsByPs endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/slot/practitioner",
 *   method = "POST",
 *   query = {
 *    "start_date": null,
 *    "end_date": null,
 *    "with_orientation": null,
 *   }
 * )
 */
class GetSlotsByPs extends AbstractSasClientPluginBase {

}
