<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\Aggregator;

use Drupal\sas_api_client\Plugin\AbstractAggregatorClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "lrms",
 *   label = @Translation("AGGREGATOR LRM origin check"),
 *   category = "aggregator",
 *   endpoint = "/{version}/samulrms",
 *   method = "GET",
 *   query = {
 *    "group": "get",
 *    "is_active": 1,
 *   }
 * )
 */
class LrmOrigin extends AbstractAggregatorClientPluginBase {

}
