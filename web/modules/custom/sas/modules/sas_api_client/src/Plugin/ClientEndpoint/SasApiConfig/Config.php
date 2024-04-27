<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApiConfig;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "config",
 *   label = @Translation("CONFIG-API Config endpoint"),
 *   category = "sas_api_config",
 *   endpoint = "/{version}/config/{id}",
 *   method = "GET",
 *   exposed = TRUE
 * )
 */
class Config extends AbstractSasClientPluginBase {

  protected const ALLOWED_CONFIGS = [
    'popin_snp',
    'snp_options',
    'homepage',
    'orientation_general',
    'reorientation',
    'sas_participation',
  ];

  /**
   * {@inheritdoc}
   */
  public function access(array $params = []) {

    if (!in_array(($params['tokens']['id'] ?? NULL), static::ALLOWED_CONFIGS)) {
      return FALSE;
    }

    return parent::access();
  }

}
