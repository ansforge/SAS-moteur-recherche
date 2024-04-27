<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "orientation",
 *   label = @Translation("SAS-API Orientation endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/orientation",
 *   api_user = "write",
 *   method = "POST",
 *   exposed = TRUE
 * )
 */
class Orientation extends AbstractSasClientPluginBase {

  /**
   * {@inheritdoc}
   */
  public function access(array $params = []) {
    if (!$this->currentUser->isAuthenticated()) {
      return FALSE;
    }
    return parent::access($params);
  }

}
