<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "get_slots_by_schedule_ids",
 *   label = @Translation("SAS-API GetSlotsByScheduleIds endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/slot/by-schedule-ids",
 *   method = "POST",
 *   exposed = TRUE,
 *   query = {
 *    "start_date": null,
 *    "end_date": null,
 *    "show_expired": null,
 *    "orientation_strategy": null,
 *   },
 *   body = {
 *    "schedule_ids": {}
 *   }
 * )
 */
class GetSlotsByScheduleIds extends AbstractSasClientPluginBase {

}
