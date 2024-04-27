<?php

namespace Drupal\sas_user\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for sas_user options.
 */
class EntityAutocompleteCity extends ControllerBase {

  /**
   * Returns response for the sas_user options autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   A JSON response containing the autocomplete suggestions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function handleAutocomplete(Request $request): CacheableJsonResponse {
    $q = $request->query->get('q');

    $term_options_storage = $this->entityTypeManager()
      ->getStorage('taxonomy_term');

    $query = $term_options_storage->getQuery()->accessCheck()
      ->condition('vid', 'cities', '=')
      ->condition('field_verified', 3, '!=')
      ->condition('name', $q . '%', 'LIKE')
      ->range(0, 10);

    $entity_ids = $query->execute();
    if (empty($entity_ids)) {
      return new CacheableJsonResponse([]);
    }

    $terms = $term_options_storage->loadMultiple($entity_ids);
    $matches = [];
    foreach ($terms as $term) {
      $matches[] = [
        'value' => EntityAutocomplete::getEntityLabels([$term]),
        'label' => sprintf('%s (%s)', $term->label(), $term->get('field_postal_code')->value ?? ''),
      ];
    }

    return new CacheableJsonResponse($matches);
  }

}
