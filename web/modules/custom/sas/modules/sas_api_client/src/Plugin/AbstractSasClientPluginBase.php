<?php

namespace Drupal\sas_api_client\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\node\NodeInterface;

/**
 * Base class for Sas api endpoint plugin plugins.
 */
abstract class AbstractSasClientPluginBase extends ClientEndpointPluginBase {

  /**
   * Current API version.
   */
  protected const API_VERSION = 'v2';

  /**
   * {@inheritdoc}
   */
  protected function setEnvironment() {
    if ($this->sasApiSettings instanceof ImmutableConfig) {
      $this->sasApiEnvironment = $this->settings->get('sas_api_env') ?? NULL;
    }
    if (empty($this->sasApiEnvironment)) {
      throw new PluginException("L'environnement SAS-API n'a pas été défini ou n'existe pas.");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function addHeaders(array &$requestOptions) {
    if ($this->sasApiSettings->get('config.sas_api.dev_mode')) {
      $requestOptions['verify'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function snpGrant(array $params) {
    if (!empty($params['tokens']['id'])) {
      $node = parent::getSnpByScheduleId($params['tokens']['id']);
      if ($node instanceof NodeInterface) {
        if ($node->access('view')) {
          return parent::access($params);
        }
      }
    }
    return FALSE;
  }

}
