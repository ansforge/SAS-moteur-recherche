<?php

namespace Drupal\sas_api_client\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines an interface for Sas api endpoint plugin plugins.
 */
interface ClientEndpointPluginInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Request the endpoint.
   *
   * @param array $params
   *   The request params.
   *
   * @return mixed
   *   The API response if any.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function request(array $params = []);

  /**
   * Check endpoint access.
   *
   * @param array $params
   *   The check params if any.
   *
   * @return bool
   *   TRUE if access is allowed.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function access(array $params = []);

}
