<?php

namespace Drupal\sas_api_client\Plugin\ClientEndpoint\SasApiConfig;

use Drupal\sas_api_client\Plugin\AbstractSasClientPluginBase;

/**
 * @ClientEndpointPlugin(
 *   id = "config_group",
 *   label = @Translation("CONFIG-API Config Group endpoint"),
 *   category = "sas_api_config",
 *   endpoint = "/{version}/config/group/{id}",
 *   exposed = TRUE
 * )
 */
class ConfigGroup extends AbstractSasClientPluginBase {

  protected const ALLOWED_GROUP_CONFIGS = [
    'snp',
    'general',
    'services',
    'group_suppression_snp',
  ];

  /**
   * {@inheritdoc}
   */
  public function access(array $params = []) {

    if (!in_array(($params['tokens']['id'] ?? NULL), static::ALLOWED_GROUP_CONFIGS)) {
      return FALSE;
    }

    return parent::access();
  }

}
