<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\Aggregator;

use Drupal\Component\Serialization\Json;
use Drupal\sas_api_client\Plugin\AbstractAggregatorClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "aggregator_login",
 *   label = @Translation("AGGREGATOR Login endpoint"),
 *   category = "aggregator",
 *   endpoint = "/{version}/login_check",
 *   method = "POST",
 *   api_user = NULL
 * )
 */
class Login extends AbstractAggregatorClientPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function addAuthentication(array &$requestOptions) {}

  protected function buildBody(array &$requestOptions) {
    $api_user = $this->requestParams['api_user'];
    $requestOptions['body'] = Json::encode([
      'username' => $this->sasApiSettings->get('config.aggregator_api.' . $api_user . '.username'),
      'password' => $this->sasApiSettings->get('config.aggregator_api.' . $api_user . '.password'),
    ]);
  }

}
