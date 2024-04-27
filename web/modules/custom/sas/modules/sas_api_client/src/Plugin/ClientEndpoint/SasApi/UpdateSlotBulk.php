<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "update_slot_bulk",
 *   label = @Translation("SAS-API UpdateSlotBulk endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/slot/bulk",
 *   api_user = "write",
 *   method = "PUT"
 * )
 */
class UpdateSlotBulk extends AbstractSasClientPluginBase {

}
