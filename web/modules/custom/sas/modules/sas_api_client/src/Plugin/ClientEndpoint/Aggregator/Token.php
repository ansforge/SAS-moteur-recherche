<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\Aggregator;

/**
 * @ClientEndpointPlugin(
 *   id = "token",
 *   label = @Translation("AGGREGATOR Token endpoint"),
 *   category = "aggregator",
 *   endpoint = "/{version}/login_check",
 *   method = "POST",
 *   exposed = TRUE
 * )
 */
class Token extends Login {

  /**
   * {@inheritdoc}
   */
  public function request(array $params = []) {
    return $params['api_token'] ?? NULL;
  }

}
