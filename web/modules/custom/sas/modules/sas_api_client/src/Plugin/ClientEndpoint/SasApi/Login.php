<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\Component\Serialization\Json;
use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "sas_api_login",
 *   label = @Translation("SAS Login endpoint"),
 *   category = "sas_api",
 *   endpoint = "/login_check",
 *   method = "POST",
 *   api_user = NULL
 * )
 */
class Login extends AbstractSasClientPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function addAuthentication(array &$requestOptions) {}

  protected function buildBody(array &$requestOptions) {
    $api_user = $this->requestParams['api_user'];
    $requestOptions['body'] = Json::encode([
      'username' => $this->sasApiSettings->get('config.sas_api.' . $api_user . '.username'),
      'password' => $this->sasApiSettings->get('config.sas_api.' . $api_user . '.password'),
    ]);
  }

}
