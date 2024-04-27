<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "delete_slot_disabled",
 *   label = @Translation("SAS-API Delete slot disabled endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/slot-disabled/{id}",
 *   api_user = "write",
 *   method = "DELETE"
 * )
 */
class DeleteSlotDisabled extends AbstractSasClientPluginBase {

}
