<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "update_slot_disabled",
 *   label = @Translation("SAS-API Update slot disabled endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/slot-disabled/{id}",
 *   api_user = "write",
 *   method = "PUT",
 *   body = {
 *     "slot": {
 *       "id": NULL
 *     },
 *     "date": NULL
 *   }
 * )
 */
class UpdateSlotDisabled extends AbstractSasClientPluginBase {

}
