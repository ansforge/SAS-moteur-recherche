<?php

namespace Drupal\sas_api_client\Plugin;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Sas api client endpoint plugin plugin manager.
 */
class ClientEndpointPluginManager extends DefaultPluginManager implements CategorizingPluginManagerInterface {

  use CategorizingPluginManagerTrait;
  use LoggerChannelTrait;

  /**
   * Constructs a new ApiEndpointPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ClientEndpoint',
      $namespaces,
      $module_handler,
      'Drupal\sas_api_client\Plugin\ClientEndpointPluginInterface',
      'Drupal\sas_api_client\Annotation\ClientEndpointPlugin'
    );

    $this->alterInfo('sas_api_client_endpoint_plugin_info');
    $this->setCacheBackend($cache_backend, 'sas_api_client_endpoint_plugin_info');
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedDefinitions(array $definitions = NULL, string $label_key = 'label') {
    /** @var \Drupal\Core\Plugin\CategorizingPluginManagerTrait|\Drupal\Component\Plugin\PluginManagerInterface $this */
    $definitions = $this->getSortedDefinitions($definitions ?? $this->getDefinitions(), $label_key);
    $grouped_definitions = [];

    foreach ($definitions as $id => $definition) {
      $grouped_definitions[(string) $definition['category']][$id] = $definition;
    }

    return $grouped_definitions;
  }

  /**
   * Magic method to provide API client methods.
   *
   * Arguments are processed like :
   *  - $name : The plugin category endpoint to call.
   *  - $args[0] : The plugin_id in the category.
   *  - $args[1] : The request data.
   *
   * The method should call request() method only.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function __call(string $name, array $arguments) {
    $definitions = $this->getGroupedDefinitions()[$name] ?? [];
    $plugin_id = $arguments[0] ?? NULL;
    $params = $arguments[1] ?? [];

    if (!empty($definitions) && isset($definitions[$plugin_id])) {
      try {
        /** @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginInterface $endpoint */
        $endpoint = $this->createInstance($plugin_id);

        if (($params['access_check'] ?? FALSE) === TRUE && !$endpoint->access($params ?? [])) {
          $message = $this->t('SAS-API Access denied for endpoint @name:@plugin_id', [
            '@name' => $name,
            '@plugin_id' => $plugin_id,
          ]);

          $this->getLogger('sas_api.server')->error($message);
          throw new PluginException('Access denied.');
        }

        $api_user = $endpoint->getPluginDefinition()['api_user'];
        if ($api_user) {
          $params['api_token'] = $this->getAuthenticationToken($name, $api_user);
        }

        return $endpoint->request($params);
      }
      catch (PluginException $e) {
        $get_exception = $endpoint->getPluginDefinition()['get_exception'] ?? FALSE;
        if ($get_exception) {
          throw new PluginException($e->getMessage(), $e->getCode());
        }
        return NULL;
      }
    }
    else {
      return NULL;
    }
  }

  /**
   * Get Authentication token for SAS-APIs.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getAuthenticationToken(string $name, string $api_user) {
    try {
      $loginParams = ['api_user' => $api_user];
      $token = $this->createInstance(str_replace('-', '_', $name) . '_login')->request($loginParams);
    }
    catch (PluginException $e) {
      return NULL;
    }

    return $token['token'] ?? NULL;
  }

}
