<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApi;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "address_localize",
 *   label = @Translation("SAS-API Address localize endpoint"),
 *   category = "sas_api",
 *   endpoint = "/{version}/geolocation/address/localize",
 *   method = "GET",
 *   exposed = TRUE
 * )
 */
class AddressLocalize extends AbstractSasClientPluginBase {

}
