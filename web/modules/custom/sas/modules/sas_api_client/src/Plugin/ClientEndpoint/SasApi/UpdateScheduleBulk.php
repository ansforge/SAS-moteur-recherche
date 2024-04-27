<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "update_schedule_bulk",
 *   label = @Translation("SAS-API UpdateScheduleBulk endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/schedule/bulk",
 *   api_user = "write",
 *   method = "PUT"
 * )
 */
class UpdateScheduleBulk extends AbstractSasClientPluginBase {

}
