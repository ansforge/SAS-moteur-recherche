<?php

namespace Drupal\sas_api_client\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Base class for Aggregator api endpoint plugin plugins.
 */
abstract class AbstractAggregatorClientPluginBase extends ClientEndpointPluginBase {

  /**
   * Current API version.
   */
  protected const API_VERSION = 'v1';

  /**
   * {@inheritdoc}
   */
  protected function setEnvironment() {
    if ($this->sasApiSettings instanceof ImmutableConfig) {
      $this->sasApiEnvironment = $this->settings->get('agg_api_env') ?? NULL;
    }
    if (empty($this->sasApiEnvironment)) {
      throw new PluginException("L'environnement SAS-API n'a pas été défini ou n'existe pas.");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function addHeaders(array &$requestOptions) {
    if ($this->sasApiSettings->get('config.aggregator_api.dev_mode')) {
      $requestOptions['verify'] = FALSE;
    }
  }

}
