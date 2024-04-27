<?php

namespace Drupal\sas_user\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\sas_user\FormHelpers;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for sas_user options.
 */
class EntityAutocompleteSasController extends ControllerBase {

  /**
   * Returns response for the sas_user options autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param string $type
   *   The current type adeli or rpps.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function handleAutocomplete(Request $request, string $type) {
    $q = $request->query->get('q');
    $field = $type === FormHelpers::SAS_PROFESSIONAL_ADELI_ID_TYPE ? 'field_personne_adeli_num' : 'field_identifiant_rpps';

    $node_options_storage = $this->entityTypeManager()->getStorage('node');

    $query = $node_options_storage->getQuery()->accessCheck()
      ->accessCheck()
      ->condition('status', 1)
      ->condition('type', FormHelpers::SAS_PROFESSIONAL_CT)
      ->condition($field, '%' . $q . '%', 'LIKE')
      ->range(0, 10);

    $entity_ids = $query->execute();

    if (empty($entity_ids)) {
      return new CacheableJsonResponse([]);
    }
    $nodes = $node_options_storage->loadMultiple($entity_ids);
    $matches = [];
    foreach ($nodes as $node) {
      $rawValue = $node->get($field)->getValue()[0]['value'];
      $matches[] = [
        'value' => $rawValue . ' (' . $node->id() . ')',
        'label' => $rawValue . ' (' . $node->id() . ')',
      ];
    }

    return new CacheableJsonResponse($matches);
  }

}
