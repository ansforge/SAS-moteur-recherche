<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\Aggregator;

use Drupal\sas_api_client\Plugin\AbstractAggregatorClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "practitioner",
 *   label = @Translation("AGGREGATOR Practitioner endpoint"),
 *   category = "aggregator",
 *   endpoint = "/{version}/practitioner/{id}/exist",
 *   method = "GET"
 * )
 */
class Practitioner extends AbstractAggregatorClientPluginBase {

}
