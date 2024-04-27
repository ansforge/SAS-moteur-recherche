<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "create_slot",
 *   label = @Translation("SAS-API CreateSlot endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/slot",
 *   api_user = "write",
 *   method = "POST",
 *   body = {
 *     "schedule": {
 *         "id": NULL
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
class CreateSlot extends AbstractSasClientPluginBase {

  /**
   * {@inheritdoc}
   */
  public function access(array $params = []) {
    return $this->snpGrant($params);
  }

}
