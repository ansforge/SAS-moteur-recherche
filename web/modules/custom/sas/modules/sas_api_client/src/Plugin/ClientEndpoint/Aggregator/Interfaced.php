<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\Aggregator;

use Drupal\sas_api_client\Plugin\AbstractAggregatorClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "interfaced",
 *   label = @Translation("AGGREGATOR Interfaced PS by date endpoint"),
 *   category = "aggregator",
 *   endpoint = "/{version}/index/pro-id-by-date-range",
 *   method = "GET",
 *   query = {
 *    "start": NULL,
 *    "end": NULL,
 *   }
 * )
 */
class Interfaced extends AbstractAggregatorClientPluginBase {

}
