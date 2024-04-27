<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApiConfig;

use Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi\Login as SasLogin;

/**
 * @ClientEndpointPlugin(
 *   id = "sas_api_config_login",
 *   label = @Translation("SAS Config Login endpoint"),
 *   category = "sas_api_config",
 *   endpoint = "/login_check",
 *   method = "POST",
 *   api_user = NULL
 * )
 */
class Login extends SasLogin {

}
