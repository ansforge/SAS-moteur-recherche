<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "create_slot_disabled",
 *   label = @Translation("SAS-API Create slot disabled endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/slot-disabled",
 *   api_user = "write",
 *   method = "POST",
 *   body = {
 *     "slot": {
 *       "id": NULL
 *     },
 *     "date": NULL,
 *     "recurring": NULL
 *   }
 * )
 */
class CreateSlotDisabled extends AbstractSasClientPluginBase {

}
