<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\Aggregator;

use Drupal\sas_api_client\Plugin\AbstractAggregatorClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "practitioner_national_id",
 *   label = @Translation("AGGREGATOR Practitioner endpoint"),
 *   category = "aggregator",
 *   endpoint = "/{version}/practitioners/national-id/{id}",
 *   method = "GET"
 * )
 */
class PractitionerNationalId extends AbstractAggregatorClientPluginBase {

}
