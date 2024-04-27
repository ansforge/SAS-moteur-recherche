<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\Aggregator;

use Drupal\sas_api_client\Plugin\AbstractAggregatorClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "practitioner_editor_bulk",
 *   label = @Translation("AGGREGATOR Practitioner editor bulk endpoint"),
 *   category = "aggregator",
 *   endpoint = "/{version}/practitioner-editors/{nationalId}/bulk",
 *   method = "PUT",
 *   body = {
 *    "editorIds": NULL,
 *   }
 * )
 */
class PractitionerEditorBulk extends AbstractAggregatorClientPluginBase {

}
