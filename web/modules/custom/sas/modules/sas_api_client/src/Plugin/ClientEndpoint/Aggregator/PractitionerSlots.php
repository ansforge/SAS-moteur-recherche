<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\Aggregator;

use Drupal\sas_api_client\Plugin\AbstractAggregatorClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "practitioner_slots",
 *   label = @Translation("AGGREGATOR Practitioner slots endpoint"),
 *   category = "aggregator",
 *   endpoint = "/{version}/PractitionerSlots",
 *   method = "POST"
 * )
 */
class PractitionerSlots extends AbstractAggregatorClientPluginBase {

}
