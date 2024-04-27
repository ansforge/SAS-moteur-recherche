<?php

namespace Drupal\sas_territory\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class JsonApiTerritoryController
 *
 * Provide endpoint relative to territories.
 *
 * @package Drupal\sas_territory\Controller
 */
class JsonApiTerritoryController extends ControllerBase {

  public function territoryList() {

    /** @var \Drupal\taxonomy\TermInterface[] $terms */
    $terms = $this->entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'sas_territoire']);

    $territories = [];
    if (!empty($terms)) {
      foreach ($terms as $term) {
        $territories[] = [
          'name' => $term->getName(),
          'drupalId' => $term->id(),
          'sasApiId' => $term->hasField('field_sas_api_id_territory') && !$term->get('field_sas_api_id_territory')
            ->isEmpty()
            ? $term->get('field_sas_api_id_territory')->value
            : NULL,
        ];
      }
    }

    $response = new CacheableJsonResponse($territories);
    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->addCacheTags(['taxonomy_term_list:sas_territoire']);
    $response->addCacheableDependency($cacheableMetadata);
    return $response;
  }

}
