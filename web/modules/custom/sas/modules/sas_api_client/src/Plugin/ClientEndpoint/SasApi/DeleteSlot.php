<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "delete_slot",
 *   label = @Translation("SAS-API DeleteSlot endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/slot/{id}",
 *   api_user = "write",
 *   method = "DELETE"
 * )
 */
class DeleteSlot extends AbstractSasClientPluginBase {

  /**
   * {@inheritdoc}
   */
  public function access(array $params = []) {
    return $this->snpGrant($params);
  }

}
