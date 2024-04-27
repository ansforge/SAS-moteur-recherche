<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "faq_reorientation",
 *   label = @Translation("SAS-API Faq reorientation endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/faq/reorientation",
 *   method = "GET"
 * )
 */
class FaqReorientation extends AbstractSasClientPluginBase {

}
