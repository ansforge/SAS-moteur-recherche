<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "get_slot_orientations",
 *   label = @Translation("SAS-API GetSlot orientations endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/slot/{id}/orientations",
 *   method = "GET",
 *   exposed = TRUE
 * )
 */
class GetSlotOrientation extends AbstractSasClientPluginBase {

}
