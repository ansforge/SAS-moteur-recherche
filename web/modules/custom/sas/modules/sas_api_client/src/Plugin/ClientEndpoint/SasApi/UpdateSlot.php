<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "update_slot",
 *   label = @Translation("SAS-API UpdateSlot endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/slot/{id}",
 *   api_user = "write",
 *   method = "PUT",
 *   body = {
 *    "schedule": {
 *      "id": NULL
 *    },
 *    "type": NULL,
 *    "start_hours": NULL,
 *    "end_hours": NULL,
 *    "date": NULL,
 *    "modalities": {},
 *    "max_patients": NULL
 *   }
 * )
 */
class UpdateSlot extends AbstractSasClientPluginBase {

  /**
   * {@inheritdoc}
   */
  public function access(array $params = []) {
    return $this->snpGrant($params);
  }

}
