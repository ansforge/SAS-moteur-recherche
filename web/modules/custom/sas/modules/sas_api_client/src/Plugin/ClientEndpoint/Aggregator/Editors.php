<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\Aggregator;

use Drupal\sas_api_client\Plugin\AbstractAggregatorClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "editors",
 *   label = @Translation("AGGREGATOR Active editors by group name"),
 *   category = "aggregator",
 *   endpoint = "/{version}/editors",
 *   method = "GET",
 *   query = {
 *    "group": "id-name",
 *    "is_active": 1,
 *   }
 * )
 */
class Editors extends AbstractAggregatorClientPluginBase {

}
