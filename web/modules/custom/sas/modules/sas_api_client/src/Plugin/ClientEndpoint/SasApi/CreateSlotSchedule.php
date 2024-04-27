<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "create_slot_schedule",
 *   label = @Translation("SAS-API CreateSlot endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/slot",
 *   api_user = "write",
 *   method = "POST",
 *   body = {
 *     "schedule": {
 *         "organization": {
 *             "rpps_rang": NULL,
 *             "finess": NULL,
 *             "siret": NULL,
 *         },
 *         "practitioner": {
 *             "pro_id": NULL
 *         }
 *     },
 *     "date": NULL,
 *     "day": NULL,
 *     "type": NULL,
 *     "start_hours": NULL,
 *     "end_hours": NULL,
 *     "modalities": {},
 *     "max_patients": NULL
 *   }
 * )
 */
class CreateSlotSchedule extends AbstractSasClientPluginBase {

}
