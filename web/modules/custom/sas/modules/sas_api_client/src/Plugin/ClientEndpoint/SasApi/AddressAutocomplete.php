<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "address_autocomplete",
 *   label = @Translation("SAS-API Address autocomplete endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/geolocation/address/autocomplete",
 *   method = "GET",
 *   exposed = TRUE
 * )
 */
class AddressAutocomplete extends AbstractSasClientPluginBase {

}
