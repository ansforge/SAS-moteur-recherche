<?php

namespace Drupal\sas_territory\Services;

use Drupal\sas_api_client\Plugin\ClientEndpointPluginManager;
use Drupal\taxonomy\TermInterface;

/**
 * Class TerritoryManager.
 *
 * Territory term manager.
 *
 * @package Drupal\sas_territory\Services
 */
class TerritoryManager implements TerritoryManagerInterface {

  /**
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected ClientEndpointPluginManager $sasApiClientManager;

  public function __construct(ClientEndpointPluginManager $sas_api_client_manager) {
    $this->sasApiClientManager = $sas_api_client_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function synchronizeWithSasApi(TermInterface $term, string $action): ?int {
    if ($term->bundle() !== 'sas_territoire' || !in_array($action, ['create', 'update', 'delete'])) {
      return NULL;
    }

    if ($term->hasField('field_sas_api_id_territory') && !$term->get('field_sas_api_id_territory')->isEmpty()) {
      $sas_api_id = $term->get('field_sas_api_id_territory')->value;
    }

    $options = [];

    $endpoint = sprintf('%s_territory', $action);
    switch ($action) {
      case 'create':
        $options = [
          'body' => [
            'name' => $term->getName(),
          ],
        ];
        break;

      case 'update':
        if (!empty($sas_api_id)) {
          $options = [
            'tokens' => [
              'id' => $sas_api_id,
            ],
            'body' => [
              'id' => $sas_api_id,
              'name' => $term->getName(),
            ],
          ];
        }
        break;

      case 'delete':
        if (!empty($sas_api_id)) {
          $options = [
            'tokens' => [
              'id' => $sas_api_id,
            ],
          ];
        }
        break;
    }

    if (!empty($endpoint) && !empty($options)) {
      $result = $this->sasApiClientManager->sas_api($endpoint, $options);
      if (!empty($result['id'])) {
        return $result['id'];
      }
    }

    return NULL;
  }

}
