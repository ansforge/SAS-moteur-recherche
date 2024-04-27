<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "schedule",
 *   label = @Translation("SAS-API Schedule endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/schedule/{id}",
 *   query = {
 *    "start_date": NULL,
 *    "end_date": NULL,
 *    "orientationStrategy": null,
 *    "show_expired": null,
 *   },
 *   exposed = TRUE
 * )
 */
class Schedule extends AbstractSasClientPluginBase {

  /**
   * {@inheritdoc}
   */
  public function access(array $params = []) {
    if ($this->pscUser->isValid() || $this->currentUser->isAuthenticated()) {
      return parent::access($params);
    }
    return FALSE;
  }

}
