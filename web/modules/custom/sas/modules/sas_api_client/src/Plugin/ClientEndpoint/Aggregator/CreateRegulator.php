<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\Aggregator;

use Drupal\sas_api_client\Plugin\AbstractAggregatorClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "create_regulator",
 *   label = @Translation("AGGREGATOR Create regulator endpoint"),
 *   category = "aggregator",
 *   endpoint = "/{version}/regulators",
 *   method = "PUT",
 *   get_exception = TRUE,
 *   body = {
 *    "uuid": NULL,
 *    "lastName": NULL,
 *    "firstName": NULL,
 *    "email": NULL,
 *    "nationalId": NULL,
 *    "habilitation": NULL,
 *    "emailBeforeUpdate": NULL,
 *    "sasTerritories": {},
 *   }
 * )
 */
class CreateRegulator extends AbstractAggregatorClientPluginBase {

}
