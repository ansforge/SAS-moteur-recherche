<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "get_hours_count_by_ps",
 *   label = @Translation("SAS-API GetHoursCountByPs endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/hours_count/practitioner_ids",
 *   method = "POST",
 *   query = {
 *    "start_date": null,
 *    "end_date": null,
 *    "output": "full|light"
 *   }
 * )
 */
class HoursCount extends AbstractSasClientPluginBase {

}
