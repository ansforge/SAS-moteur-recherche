<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "faq",
 *   label = @Translation("SAS-API Faq endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/faq",
 *   method = "GET",
 *   query = {
 *    "has_snp": NULL,
 *   },
 *   exposed = TRUE
 * )
 */
class Faq extends AbstractSasClientPluginBase {

}
