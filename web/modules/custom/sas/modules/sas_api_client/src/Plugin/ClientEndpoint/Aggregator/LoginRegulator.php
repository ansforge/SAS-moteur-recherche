<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\Aggregator;

use Drupal\sas_api_client\Plugin\AbstractAggregatorClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "login_regulator",
 *   label = @Translation("AGGREGATOR login regulator endpoint"),
 *   category = "aggregator",
 *   endpoint = "/{version}/regulators/login",
 *   method = "PUT",
 *   get_exception = TRUE,
 *   body = {
 *    "uuid": NULL,
 *    "lastName": NULL,
 *    "firstName": NULL,
 *    "email": NULL,
 *    "nationalId": NULL,
 *    "habilitation": NULL,
 *    "sasTerritories": {},
 *   }
 * )
 */
class LoginRegulator extends AbstractAggregatorClientPluginBase {

}
